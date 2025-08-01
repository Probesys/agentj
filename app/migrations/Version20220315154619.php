<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
// phpcs:disable Generic.Files.LineLength
final class Version20220315154619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \' \' NOT NULL');
        $this->addSql('ALTER TABLE users ADD prefered_lang VARCHAR(5) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE users DROP prefered_lang');
    }
}
