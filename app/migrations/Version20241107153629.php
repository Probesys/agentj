<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20241107153629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename existing column id to mail_id and create a new id column as an auto-incrementing integer primary key';
    }

    public function up(Schema $schema): void
    {
        // Rename the existing id column to mail_id
        $this->addSql('ALTER TABLE sql_limit_report CHANGE id mail_id VARCHAR(320) NOT NULL');

        // Add the new id column as an auto-incrementing integer primary key
        $this->addSql('ALTER TABLE sql_limit_report ADD id INT AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST');
    }

    public function down(Schema $schema): void
    {
        // Drop the new id column
        $this->addSql('ALTER TABLE sql_limit_report DROP COLUMN id');

        // Rename mail_id back to id
        $this->addSql('ALTER TABLE sql_limit_report CHANGE mail_id id VARCHAR(320) NOT NULL');
    }
}
