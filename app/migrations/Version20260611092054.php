<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611092054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add columns to track async connector tasks result';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE connector
            ADD last_success_result JSON DEFAULT '[]' NOT NULL,
            ADD last_success_at DATETIME DEFAULT NULL,
            ADD last_error_result LONGTEXT DEFAULT '' NOT NULL,
            ADD last_error_at DATETIME DEFAULT NULL,
            CHANGE import_started_at import_started_at DATETIME DEFAULT NULL;
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE domain
            CHANGE report_spam_level report_spam_level DOUBLE PRECISION DEFAULT 0 NOT NULL,
            CHANGE authorized_senders_spam_level authorized_senders_spam_level DOUBLE PRECISION DEFAULT 5 NOT NULL;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE connector
            DROP last_success_result,
            DROP last_success_at,
            DROP last_error_result,
            DROP last_error_at,
            CHANGE import_started_at import_started_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)';
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE domain
            CHANGE authorized_senders_spam_level authorized_senders_spam_level DOUBLE PRECISION DEFAULT '5' NOT NULL,
            CHANGE report_spam_level report_spam_level DOUBLE PRECISION DEFAULT '0' NOT NULL;
        SQL);
    }
}
