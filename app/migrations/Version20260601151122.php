<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601151122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make message captcha validation toggle not nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE msgs SET validate_captcha = 0 WHERE validate_captcha IS NULL');
        $this->addSql('UPDATE out_msgs SET validate_captcha = 0 WHERE validate_captcha IS NULL');
        $this->addSql('ALTER TABLE msgs CHANGE validate_captcha validate_captcha INT UNSIGNED DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE out_msgs CHANGE validate_captcha validate_captcha INT UNSIGNED DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE out_msgs CHANGE validate_captcha validate_captcha INT UNSIGNED DEFAULT 0');
        $this->addSql('ALTER TABLE msgs CHANGE validate_captcha validate_captcha INT UNSIGNED DEFAULT 0');
    }
}
