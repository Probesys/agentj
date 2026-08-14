<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * This migration has been commented in 2.7.1 as we realized too late that
 * executing it would lock the database for too long on huge tables.
 *
 * As this change (changing validate_captcha from "NULL" to "NOT NULL") isn't
 * really useful, we prefered to comment the SQL queries.
 *
 * This file is kept and another migration (Version20260814081035) is provided
 * to revert the change for those who migrated to 2.7.0.
 */
final class Version20260601151122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make message captcha validation toggle not nullable';
    }

    public function up(Schema $schema): void
    {
        //$this->addSql('UPDATE msgs SET validate_captcha = 0 WHERE validate_captcha IS NULL');
        //$this->addSql('UPDATE out_msgs SET validate_captcha = 0 WHERE validate_captcha IS NULL');
        //$this->addSql(
        //    'ALTER TABLE msgs CHANGE validate_captcha validate_captcha INT UNSIGNED DEFAULT 0 NOT NULL'
        //);
        //$this->addSql(
        //    'ALTER TABLE out_msgs CHANGE validate_captcha validate_captcha INT UNSIGNED DEFAULT 0 NOT NULL'
        //);
    }

    public function down(Schema $schema): void
    {
        //$this->addSql('ALTER TABLE out_msgs CHANGE validate_captcha validate_captcha INT UNSIGNED DEFAULT 0');
        //$this->addSql('ALTER TABLE msgs CHANGE validate_captcha validate_captcha INT UNSIGNED DEFAULT 0');
    }
}
