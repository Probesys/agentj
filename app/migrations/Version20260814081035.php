<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * This migration reverts the changes from Version20260601151122 only for those
 * who applied it in 2.7.0 (we cancelled it as it would take too much time on
 * huge databases).
 */
final class Version20260814081035 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Revert msgs and out_msgs validate_captcha not nullable.';
    }

    public function up(Schema $schema): void
    {
        $isNullable = $this->connection->fetchOne(<<<SQL
            SELECT IS_NULLABLE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'msgs'
            AND COLUMN_NAME = 'validate_captcha';
            SQL);

        $this->skipIf($isNullable === 'YES', 'msgs.validate_captcha is already nullable.');

        $this->addSql('ALTER TABLE msgs CHANGE validate_captcha validate_captcha INT UNSIGNED DEFAULT 0');
        $this->addSql('ALTER TABLE out_msgs CHANGE validate_captcha validate_captcha INT UNSIGNED DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE msgs CHANGE validate_captcha validate_captcha INT UNSIGNED DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE out_msgs CHANGE validate_captcha validate_captcha INT UNSIGNED DEFAULT 0 NOT NULL');
    }
}
