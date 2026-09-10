<?php

namespace App\Tests\Migration;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\GroupFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\AbortMigration;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RuleAddressNormalizationMigrationTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = static::getContainer()->get('doctrine.dbal.default_connection');
        $this->connection->executeStatement('DROP TEMPORARY TABLE IF EXISTS out_wblist');
        $this->connection->executeStatement(<<<'SQL'
            CREATE TEMPORARY TABLE out_wblist (
                rid INT UNSIGNED NOT NULL,
                sid INT UNSIGNED NOT NULL,
                priority INT NOT NULL,
                group_id INT DEFAULT NULL,
                wb VARCHAR(10) NOT NULL,
                datemod DATETIME DEFAULT CURRENT_TIMESTAMP,
                type INT DEFAULT NULL,
                PRIMARY KEY (rid, sid, priority)
            ) ENGINE=InnoDB
        SQL);
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('DROP TEMPORARY TABLE IF EXISTS out_wblist');
        parent::tearDown();
    }

    public function testItNormalizesAddressesAndKeepsTheLatestRule(): void
    {
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $group = GroupFactory::createOne(['domain' => $domain]);
        $uppercaseId = $this->insertRuleAddress('Sender@Example.org', priority: 99);
        $lowercaseId = $this->insertRuleAddress('sender@example.org', priority: 42);
        $this->insertRuleAddress('Unique@Example.org');
        $this->insertRuleAddress('@.Example.org', priority: 99);
        $this->connection->executeStatement("UPDATE mailaddr SET priority = 99 WHERE email = BINARY '@.'");

        $this->connection->executeStatement(<<<'SQL'
            INSERT INTO wblist (rid, sid, group_id, wb, datemod, type, priority)
            VALUES
                (:rid, :uppercaseSid, NULL, 'W', '2026-09-09 10:00:00', 3, 100),
                (:rid, :lowercaseSid, NULL, 'Y', '2026-09-10 10:00:00', 3, 100),
                (:rid, :uppercaseSid, NULL, 'B', '2026-09-09 10:00:00', 2, 50)
        SQL, [
            'rid' => $user->getId(),
            'uppercaseSid' => $uppercaseId,
            'lowercaseSid' => $lowercaseId,
        ]);
        $this->connection->executeStatement(<<<'SQL'
            INSERT INTO groups_wblist (group_id, sid, wb)
            VALUES (:groupId, :uppercaseSid, 'B'), (:groupId, :lowercaseSid, 'W')
        SQL, [
            'groupId' => $group->getId(),
            'uppercaseSid' => $uppercaseId,
            'lowercaseSid' => $lowercaseId,
        ]);
        $this->connection->executeStatement(<<<'SQL'
            INSERT INTO out_wblist (rid, sid, group_id, wb, datemod, type, priority)
            VALUES
                (:rid, :uppercaseSid, NULL, 'B', '2026-09-11 10:00:00', 4, 100),
                (:rid, :lowercaseSid, NULL, 'W', '2026-09-10 10:00:00', 5, 100),
                (:rid, :lowercaseSid, NULL, 'Y', '2026-09-10 10:00:00', 4, 50)
        SQL, [
            'rid' => $user->getId(),
            'uppercaseSid' => $uppercaseId,
            'lowercaseSid' => $lowercaseId,
        ]);

        $this->runMigration();

        self::assertSame(1, (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM mailaddr WHERE LOWER(CONVERT(email USING utf8mb4)) = 'sender@example.org'",
        ));
        self::assertSame(1, (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM mailaddr WHERE email = BINARY 'unique@example.org'",
        ));
        self::assertSame(2, (int) $this->connection->fetchOne(
            "SELECT priority FROM mailaddr WHERE email = BINARY '@.example.org'",
        ));
        self::assertSame(0, (int) $this->connection->fetchOne(
            "SELECT priority FROM mailaddr WHERE email = BINARY '@.'",
        ));
        self::assertSame(2, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM wblist WHERE rid = ?',
            [$user->getId()],
        ));
        self::assertSame(1, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM groups_wblist WHERE group_id = ?',
            [$group->getId()],
        ));
        self::assertSame('Y', $this->connection->fetchOne(
            'SELECT wb FROM wblist WHERE rid = ? AND priority = 100',
            [$user->getId()],
        ));
        self::assertSame('B', $this->connection->fetchOne(
            'SELECT wb FROM wblist WHERE rid = ? AND priority = 50',
            [$user->getId()],
        ));
        self::assertSame('B', $this->connection->fetchOne(
            'SELECT wb FROM groups_wblist WHERE group_id = ?',
            [$group->getId()],
        ));
        self::assertSame(6, (int) $this->connection->fetchOne(
            "SELECT priority FROM mailaddr WHERE email = BINARY 'sender@example.org'",
        ));
        self::assertSame(2, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM out_wblist WHERE rid = ?',
            [$user->getId()],
        ));
        self::assertSame('B', $this->connection->fetchOne(
            'SELECT wb FROM out_wblist WHERE rid = ? AND priority = 100',
            [$user->getId()],
        ));
        self::assertSame($lowercaseId, (int) $this->connection->fetchOne(
            'SELECT sid FROM out_wblist WHERE rid = ? AND priority = 100',
            [$user->getId()],
        ));
        self::assertSame('Y', $this->connection->fetchOne(
            'SELECT wb FROM out_wblist WHERE rid = ? AND priority = 50',
            [$user->getId()],
        ));
        $details = $this->conflictLogDetails();
        self::assertCount(2, $details);
        self::assertSame(['groups_wblist', 'out_wblist'], array_column($details, 'table'));
        foreach ($details as $detail) {
            self::assertSame($lowercaseId, $detail['kept_sid']);
            self::assertSame($uppercaseId, $detail['kept_source_sid']);
            self::assertSame('block', $detail['kept_action']);
            self::assertSame('allow', $detail['discarded_action']);
        }
    }

    public function testItResolvesContradictoryRulesByModificationDate(): void
    {
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $lowercaseId = $this->insertRuleAddress('sender@example.org');
        $uppercaseId = $this->insertRuleAddress('Sender@Example.org');
        $group = GroupFactory::createOne(['domain' => $domain]);
        $this->connection->executeStatement(<<<'SQL'
            INSERT INTO wblist (rid, sid, group_id, wb, datemod, type, priority)
            VALUES
                (:rid, :lowercaseSid, NULL, 'B', '2026-09-10 10:00:00', 3, 100),
                (:rid, :uppercaseSid, NULL, 'W', '2026-09-09 10:00:00', 3, 100)
        SQL, [
            'rid' => $user->getId(),
            'uppercaseSid' => $uppercaseId,
            'lowercaseSid' => $lowercaseId,
        ]);
        $this->connection->executeStatement(<<<'SQL'
            INSERT INTO groups_wblist (group_id, sid, wb)
            VALUES (:groupId, :lowercaseSid, 'B'), (:groupId, :uppercaseSid, 'W')
        SQL, [
            'groupId' => $group->getId(),
            'lowercaseSid' => $lowercaseId,
            'uppercaseSid' => $uppercaseId,
        ]);

        $this->runMigration();

        self::assertSame(1, (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM mailaddr WHERE LOWER(CONVERT(email USING utf8mb4)) = 'sender@example.org'",
        ));
        self::assertSame(1, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM wblist WHERE rid = ?',
            [$user->getId()],
        ));
        self::assertSame('B', $this->connection->fetchOne(
            'SELECT wb FROM wblist WHERE rid = ?',
            [$user->getId()],
        ));
        self::assertSame('B', $this->connection->fetchOne(
            'SELECT wb FROM groups_wblist WHERE group_id = ?',
            [$group->getId()],
        ));
        $details = $this->conflictLogDetails();
        self::assertCount(2, $details);
        self::assertSame(['groups_wblist', 'wblist'], array_column($details, 'table'));
        foreach ($details as $detail) {
            self::assertSame($lowercaseId, $detail['kept_sid']);
            self::assertSame($lowercaseId, $detail['kept_source_sid']);
            self::assertSame('block', $detail['kept_action']);
            self::assertSame('allow', $detail['discarded_action']);
        }
    }

    public function testItUsesTheHighestAddressIdWhenModificationDatesAreEqual(): void
    {
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $group = GroupFactory::createOne(['domain' => $domain]);
        $lowercaseId = $this->insertRuleAddress('sender@example.org');
        $uppercaseId = $this->insertRuleAddress('Sender@Example.org');
        $this->connection->executeStatement(<<<'SQL'
            INSERT INTO wblist (rid, sid, group_id, wb, datemod, type, priority)
            VALUES
                (:rid, :lowercaseSid, NULL, 'W', '2026-09-10 10:00:00', 3, 100),
                (:rid, :uppercaseSid, :groupId, 'B', '2026-09-10 10:00:00', 4, 100)
        SQL, [
            'rid' => $user->getId(),
            'groupId' => $group->getId(),
            'lowercaseSid' => $lowercaseId,
            'uppercaseSid' => $uppercaseId,
        ]);
        $this->connection->executeStatement(<<<'SQL'
            INSERT INTO groups_wblist (group_id, sid, wb)
            VALUES (:groupId, :lowercaseSid, 'W'), (:groupId, :uppercaseSid, 'Y')
        SQL, [
            'groupId' => $group->getId(),
            'lowercaseSid' => $lowercaseId,
            'uppercaseSid' => $uppercaseId,
        ]);

        $this->runMigration();

        $rule = $this->connection->fetchAssociative(
            'SELECT sid, group_id, wb, type FROM wblist WHERE rid = ?',
            [$user->getId()],
        );
        self::assertIsArray($rule);
        self::assertSame($lowercaseId, (int) $rule['sid']);
        self::assertSame($group->getId(), (int) $rule['group_id']);
        self::assertSame('B', $rule['wb']);
        self::assertSame(4, (int) $rule['type']);
        self::assertSame('Y', $this->connection->fetchOne(
            'SELECT wb FROM groups_wblist WHERE group_id = ?',
            [$group->getId()],
        ));
        $details = $this->conflictLogDetails();
        self::assertCount(1, $details);
        self::assertSame('wblist', $details[0]['table']);
    }

    public function testItRejectsDatabaseDependentUnicodeNormalization(): void
    {
        $this->insertRuleAddress('İ@example.org');

        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('Unicode case folding');

        $this->runMigration();
    }

    public function testItAcceptsMatchingUnicodeNormalization(): void
    {
        $this->insertRuleAddress('é@Example.org');

        $this->runMigration();

        self::assertSame(1, (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM mailaddr WHERE email = BINARY 'é@example.org'",
        ));
    }

    public function testGeneratedSqlContainsTheAddressMapBeforeItsUse(): void
    {
        $migration = $this->createMigration();
        $migration->up(new Schema());
        $statements = array_map(
            static fn ($query): string => $query->getStatement(),
            $migration->getSql(),
        );
        $sql = implode("\n", $statements);

        self::assertStringContainsString('CREATE TEMPORARY TABLE tmp_rule_address_normalization_map', $sql);
        self::assertLessThan(
            strpos($sql, 'CREATE TEMPORARY TABLE tmp_normalized_group_rules'),
            strpos($sql, 'CREATE TEMPORARY TABLE tmp_rule_address_normalization_map'),
        );
    }

    public function testItWorksWhenOutgoingRulesTableIsAbsent(): void
    {
        $this->connection->executeStatement('DROP TEMPORARY TABLE out_wblist');
        $this->insertRuleAddress('Unique@Example.org');

        $this->runMigration();

        self::assertSame(1, (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM mailaddr WHERE email = BINARY 'unique@example.org'",
        ));
    }

    private function insertRuleAddress(string $email, int $priority = 6): int
    {
        $this->connection->insert('mailaddr', [
            'priority' => $priority,
            'email' => $email,
        ]);

        return (int) $this->connection->fetchOne(
            'SELECT id FROM mailaddr WHERE email = BINARY ?',
            [$email],
        );
    }

    private function runMigration(): void
    {
        $migration = $this->createMigration();
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement(
                $query->getStatement(),
                $query->getParameters(),
                $query->getTypes(),
            );
        }
    }

    private function createMigration(): AbstractMigration
    {
        require_once dirname(__DIR__, 2) . '/migrations/Version20260910120000.php';

        /** @var class-string<AbstractMigration> $migrationClass */
        $migrationClass = implode('\\', ['DoctrineMigrations', 'Version20260910120000']);
        return new $migrationClass($this->connection, new NullLogger());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function conflictLogDetails(): array
    {
        return array_map(
            static fn (string $details): array => json_decode($details, true, flags: JSON_THROW_ON_ERROR),
            $this->connection->fetchFirstColumn(<<<'SQL'
                SELECT details FROM log
                WHERE action = 'migration sender rule conflict resolved'
                ORDER BY id
            SQL),
        );
    }
}
