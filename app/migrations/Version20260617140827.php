<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617140827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add columns to connector to keep track of async tasks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE connector
            ADD import_started_at DATETIME DEFAULT NULL,
            ADD last_success_result JSON DEFAULT '[]' NOT NULL,
            ADD last_success_at DATETIME DEFAULT NULL,
            ADD last_error_result LONGTEXT DEFAULT '' NOT NULL,
            ADD last_error_at DATETIME DEFAULT NULL,
            DROP last_synchronized_at,
            DROP last_result_synchronization;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE connector
            DROP import_started_at,
            DROP last_success_result,
            DROP last_success_at,
            DROP last_error_result,
            DROP last_error_at,
            ADD last_synchronized_at DATETIME DEFAULT NULL,
            ADD last_result_synchronization LONGTEXT DEFAULT NULL;
        SQL);
    }
}
