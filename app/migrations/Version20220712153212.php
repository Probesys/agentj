<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220712153212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE connector ADD created_by_id INT UNSIGNED DEFAULT NULL, ADD created DATETIME NOT NULL');
        $this->addSql('ALTER TABLE connector ADD CONSTRAINT FK_148C456EB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_148C456EB03A8386 ON connector (created_by_id)');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \' \' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE connector DROP FOREIGN KEY FK_148C456EB03A8386');
        $this->addSql('DROP INDEX IDX_148C456EB03A8386 ON connector');
        $this->addSql('ALTER TABLE connector DROP created_by_id, DROP created');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \'\' NOT NULL');
    }
}
