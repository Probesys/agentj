<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241009093048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sql_limit_report table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS sql_limit_report (
            id VARCHAR(40) NOT NULL,
            date DATETIME NOT NULL,
            recipient_count INT DEFAULT 1,
            delta INT NOT NULL
        )');
        $this->addSql('ALTER TABLE sql_limit_report ADD processed_user TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE sql_limit_report ADD processed_admin TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sql_limit_report');
    }
}