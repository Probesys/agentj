<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\Migrations\AbstractMigration;

final class Version20260910120000 extends AbstractMigration
{
    private const CONFLICT_LOG_ACTION = 'migration sender rule conflict resolved';
    private const MAP_TABLE = 'tmp_rule_address_normalization_map';
    private const GROUP_RULE_TABLE = 'tmp_normalized_group_rules';
    private const SENDER_RULE_TABLE = 'tmp_normalized_sender_rules';
    private const OUT_RULE_TABLE = 'tmp_normalized_out_rules';

    private bool $hasOutRuleTable = false;

    public function getDescription(): string
    {
        return 'Normalize and merge case-insensitive sender rule addresses, keeping the latest conflicting rule';
    }

    public function up(Schema $schema): void
    {
        $this->abortIfInvalidAddresses();
        $this->hasOutRuleTable = $this->hasOutRuleTable();
        $this->dropTemporaryTables();
        $this->addSql(<<<SQL
            CREATE TEMPORARY TABLE {$this->table(self::MAP_TABLE)} (
                old_sid INT UNSIGNED NOT NULL PRIMARY KEY,
                keep_sid INT UNSIGNED NOT NULL,
                normalized_email VARBINARY(255) NOT NULL,
                KEY IDX_RULE_ADDRESS_KEEP_SID (keep_sid)
            ) ENGINE=InnoDB
        SQL);
        $this->addSql(<<<SQL
            INSERT INTO {$this->table(self::MAP_TABLE)} (old_sid, keep_sid, normalized_email)
            SELECT old_sid, keep_sid, normalized_email
            FROM (
                SELECT
                    id AS old_sid,
                    normalized_email,
                    COUNT(*) OVER (
                        PARTITION BY normalized_email
                    ) AS duplicate_count,
                    COALESCE(
                        MIN(CASE WHEN email = normalized_email THEN id END)
                            OVER (PARTITION BY normalized_email),
                        MIN(id) OVER (PARTITION BY normalized_email)
                    ) AS keep_sid
                FROM (
                    SELECT
                        id,
                        email,
                        CAST(LOWER(CONVERT(email USING utf8mb4)) AS BINARY) AS normalized_email
                    FROM mailaddr
                ) normalized_addresses
            ) mapped_addresses
            WHERE duplicate_count > 1
        SQL);

        $this->stageGroupRules();
        $this->stageSenderRules('wblist', self::SENDER_RULE_TABLE);
        if ($this->hasOutRuleTable) {
            $this->stageSenderRules('out_wblist', self::OUT_RULE_TABLE);
        }
        $this->logGroupRuleConflicts();
        $this->logSenderRuleConflicts('wblist');
        if ($this->hasOutRuleTable) {
            $this->logSenderRuleConflicts('out_wblist');
        }

        $this->replaceAffectedRules('groups_wblist', self::GROUP_RULE_TABLE, 'group_id, sid, wb');
        $columns = 'rid, sid, group_id, wb, datemod, type, priority';
        $this->replaceAffectedRules('wblist', self::SENDER_RULE_TABLE, $columns);
        if ($this->hasOutRuleTable) {
            $this->replaceAffectedRules('out_wblist', self::OUT_RULE_TABLE, $columns);
        }

        $this->addSql(<<<SQL
            DELETE ma
            FROM mailaddr ma
            INNER JOIN {$this->table(self::MAP_TABLE)} map ON map.old_sid = ma.id
            WHERE map.old_sid <> map.keep_sid
        SQL);
        $normalizedEmail = 'CAST(LOWER(CONVERT(ma.email USING utf8mb4)) AS BINARY)';
        $computedPriority = $this->priorityExpression($normalizedEmail);
        $this->addSql(<<<SQL
            UPDATE mailaddr ma
            SET ma.email = {$normalizedEmail}, ma.priority = {$computedPriority}
            WHERE ma.email <> {$normalizedEmail} OR ma.priority <> {$computedPriority}
        SQL);
        $this->dropTemporaryTables();
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The original RuleAddress casing cannot be restored.');
    }

    private function abortIfInvalidAddresses(): void
    {
        $invalidCount = (int) $this->connection->fetchOne(<<<'SQL'
            SELECT COUNT(*)
            FROM mailaddr
            WHERE email <> CAST(CONVERT(email USING utf8mb4) AS BINARY)
               OR OCTET_LENGTH(LOWER(CONVERT(email USING utf8mb4))) > 255
        SQL);

        $this->abortIf(
            $invalidCount > 0,
            "Cannot normalize RuleAddress: {$invalidCount} address(es) contain invalid UTF-8, "
                . 'exceed 255 bytes, or require database-dependent Unicode case folding.',
        );

        $cursor = 0;
        do {
            $rows = $this->connection->fetchAllAssociative(<<<'SQL'
                SELECT
                    id,
                    email,
                    CAST(LOWER(CONVERT(email USING utf8mb4)) AS BINARY) AS normalized_email
                FROM mailaddr
                WHERE id > :cursor
                  AND OCTET_LENGTH(email) <> CHAR_LENGTH(CONVERT(email USING utf8mb4))
                ORDER BY id
                LIMIT 1000
            SQL, ['cursor' => $cursor]);

            foreach ($rows as $row) {
                $cursor = (int) $row['id'];
                $normalizedEmail = mb_strtolower((string) $row['email'], 'UTF-8');
                $this->abortIf(
                    $normalizedEmail !== $row['normalized_email'] || strlen($normalizedEmail) > 255,
                    'Cannot normalize RuleAddress: address ' . $row['id']
                        . ' requires database-dependent Unicode case folding.',
                );
            }
        } while (count($rows) === 1000);
    }

    private function hasOutRuleTable(): bool
    {
        try {
            $this->connection->fetchOne('SELECT 1 FROM out_wblist LIMIT 1');
            return true;
        } catch (TableNotFoundException) {
            return false;
        }
    }

    private function stageGroupRules(): void
    {
        $this->addSql('CREATE TEMPORARY TABLE ' . self::GROUP_RULE_TABLE . ' LIKE groups_wblist');
        $this->addSql(<<<SQL
            INSERT INTO {$this->table(self::GROUP_RULE_TABLE)} (group_id, sid, wb)
            SELECT group_id, keep_sid, wb
            FROM (
                SELECT
                    gr.group_id,
                    map.keep_sid,
                    gr.wb,
                    ROW_NUMBER() OVER (
                        PARTITION BY gr.group_id, map.keep_sid
                        ORDER BY map.old_sid DESC
                    ) AS rule_rank
                FROM groups_wblist gr
                INNER JOIN {$this->table(self::MAP_TABLE)} map ON map.old_sid = gr.sid
            ) ranked_group_rules
            WHERE rule_rank = 1
        SQL);
    }

    private function stageSenderRules(string $sourceTable, string $temporaryTable): void
    {
        $this->addSql("CREATE TEMPORARY TABLE {$temporaryTable} LIKE {$sourceTable}");
        $this->addSql(<<<SQL
            INSERT INTO {$this->table($temporaryTable)} (rid, sid, group_id, wb, datemod, type, priority)
            SELECT rid, keep_sid, group_id, wb, datemod, type, priority
            FROM (
                SELECT
                    sr.rid,
                    map.keep_sid,
                    sr.group_id,
                    sr.wb,
                    sr.datemod,
                    sr.type,
                    sr.priority,
                    ROW_NUMBER() OVER (
                        PARTITION BY sr.rid, map.keep_sid, sr.priority
                        ORDER BY sr.datemod DESC, map.old_sid DESC
                    ) AS rule_rank
                FROM {$this->table($sourceTable)} sr
                INNER JOIN {$this->table(self::MAP_TABLE)} map ON map.old_sid = sr.sid
            ) ranked_sender_rules
            WHERE rule_rank = 1
        SQL);
    }

    private function replaceAffectedRules(string $targetTable, string $temporaryTable, string $columns): void
    {
        $this->addSql(<<<SQL
            DELETE target
            FROM {$this->table($targetTable)} target
            INNER JOIN {$this->table(self::MAP_TABLE)} map ON map.old_sid = target.sid
        SQL);
        $this->addSql(<<<SQL
            INSERT INTO {$this->table($targetTable)} ({$columns})
            SELECT {$columns} FROM {$this->table($temporaryTable)}
        SQL);
    }

    private function logGroupRuleConflicts(): void
    {
        $action = $this->actionExpression('gr.wb');
        $logAction = self::CONFLICT_LOG_ACTION;
        $this->addSql(<<<SQL
            INSERT INTO log (action, mailId, details, created, updated)
            SELECT
                '{$logAction}',
                NULL,
                JSON_OBJECT(
                    'table', 'groups_wblist',
                    'address', CONVERT(normalized_email USING utf8mb4),
                    'group_id', group_id,
                    'kept_sid', winner_sid,
                    'kept_action', winner_action,
                    'kept_wb_hex', HEX(winner_wb),
                    'discarded_sid', old_sid,
                    'discarded_action', rule_action,
                    'discarded_wb_hex', HEX(wb)
                ),
                NOW(),
                NOW()
            FROM (
                SELECT
                    gr.group_id,
                    map.old_sid,
                    map.normalized_email,
                    gr.wb,
                    {$action} AS rule_action,
                    FIRST_VALUE(map.old_sid) OVER (
                        PARTITION BY gr.group_id, map.keep_sid
                        ORDER BY map.old_sid DESC
                    ) AS winner_sid,
                    FIRST_VALUE(gr.wb) OVER (
                        PARTITION BY gr.group_id, map.keep_sid
                        ORDER BY map.old_sid DESC
                    ) AS winner_wb,
                    FIRST_VALUE({$action}) OVER (
                        PARTITION BY gr.group_id, map.keep_sid
                        ORDER BY map.old_sid DESC
                    ) AS winner_action,
                    ROW_NUMBER() OVER (
                        PARTITION BY gr.group_id, map.keep_sid
                        ORDER BY map.old_sid DESC
                    ) AS rule_rank
                FROM groups_wblist gr
                INNER JOIN {$this->table(self::MAP_TABLE)} map ON map.old_sid = gr.sid
            ) ranked_rules
            WHERE rule_rank > 1 AND rule_action <> winner_action
        SQL);
    }

    private function logSenderRuleConflicts(string $table): void
    {
        $action = $this->actionExpression('sr.wb');
        $logAction = self::CONFLICT_LOG_ACTION;
        $this->addSql(<<<SQL
            INSERT INTO log (action, mailId, details, created, updated)
            SELECT
                '{$logAction}',
                NULL,
                JSON_OBJECT(
                    'table', '{$this->table($table)}',
                    'address', CONVERT(normalized_email USING utf8mb4),
                    'recipient_id', rid,
                    'priority', priority,
                    'kept_sid', winner_sid,
                    'kept_action', winner_action,
                    'kept_wb_hex', HEX(winner_wb),
                    'kept_datemod', winner_datemod,
                    'discarded_sid', old_sid,
                    'discarded_action', rule_action,
                    'discarded_wb_hex', HEX(wb),
                    'discarded_datemod', datemod
                ),
                NOW(),
                NOW()
            FROM (
                SELECT
                    sr.rid,
                    sr.priority,
                    map.old_sid,
                    map.normalized_email,
                    sr.wb,
                    sr.datemod,
                    {$action} AS rule_action,
                    FIRST_VALUE(map.old_sid) OVER (
                        PARTITION BY sr.rid, map.keep_sid, sr.priority
                        ORDER BY sr.datemod DESC, map.old_sid DESC
                    ) AS winner_sid,
                    FIRST_VALUE(sr.wb) OVER (
                        PARTITION BY sr.rid, map.keep_sid, sr.priority
                        ORDER BY sr.datemod DESC, map.old_sid DESC
                    ) AS winner_wb,
                    FIRST_VALUE(sr.datemod) OVER (
                        PARTITION BY sr.rid, map.keep_sid, sr.priority
                        ORDER BY sr.datemod DESC, map.old_sid DESC
                    ) AS winner_datemod,
                    FIRST_VALUE({$action}) OVER (
                        PARTITION BY sr.rid, map.keep_sid, sr.priority
                        ORDER BY sr.datemod DESC, map.old_sid DESC
                    ) AS winner_action,
                    ROW_NUMBER() OVER (
                        PARTITION BY sr.rid, map.keep_sid, sr.priority
                        ORDER BY sr.datemod DESC, map.old_sid DESC
                    ) AS rule_rank
                FROM {$this->table($table)} sr
                INNER JOIN {$this->table(self::MAP_TABLE)} map ON map.old_sid = sr.sid
            ) ranked_rules
            WHERE rule_rank > 1 AND rule_action <> winner_action
        SQL);
    }

    private function dropTemporaryTables(): void
    {
        foreach (
            [self::GROUP_RULE_TABLE, self::SENDER_RULE_TABLE, self::OUT_RULE_TABLE, self::MAP_TABLE] as $table
        ) {
            $this->addSql("DROP TEMPORARY TABLE IF EXISTS {$table}");
        }
    }

    private function priorityExpression(string $normalizedEmail): string
    {
        $email = "CONVERT({$normalizedEmail} USING utf8mb4)";
        $domain = "SUBSTRING({$email}, 2)";
        $domainPartCount = "LENGTH({$domain}) - LENGTH(REPLACE({$domain}, '.', '')) + 1";

        return <<<SQL
            CASE
                WHEN {$email} = '@.' THEN 0
                WHEN LEFT({$email}, 1) <> '@' THEN 6
                WHEN LEFT({$domain}, 1) <> '.' THEN 5
                WHEN ({$domainPartCount}) = 2 THEN 1
                WHEN ({$domainPartCount}) = 3 THEN 2
                WHEN ({$domainPartCount}) = 4 THEN 3
                ELSE 5
            END
        SQL;
    }

    private function actionExpression(string $column): string
    {
        return <<<SQL
            CASE HEX({$column})
                WHEN '57' THEN 'allow'
                WHEN '59' THEN 'allow'
                WHEN '42' THEN 'block'
                WHEN '4E' THEN 'block'
                WHEN '20' THEN 'accept'
                WHEN '30' THEN 'enabled'
                WHEN '' THEN 'none'
                ELSE CONCAT('raw:', HEX({$column}))
            END
        SQL;
    }

    private function table(string $table): string
    {
        return $table;
    }
}
