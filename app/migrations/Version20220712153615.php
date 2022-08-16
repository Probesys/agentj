<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220712153615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE connector ADD updated_by_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE connector ADD CONSTRAINT FK_148C456E896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_148C456E896DBBDE ON connector (updated_by_id)');
        $this->addSql('ALTER TABLE log ADD updated_by_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE log ADD CONSTRAINT FK_8F3F68C5896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8F3F68C5896DBBDE ON log (updated_by_id)');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \' \' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE connector DROP FOREIGN KEY FK_148C456E896DBBDE');
        $this->addSql('DROP INDEX IDX_148C456E896DBBDE ON connector');
        $this->addSql('ALTER TABLE connector DROP updated_by_id');
        $this->addSql('ALTER TABLE log DROP FOREIGN KEY FK_8F3F68C5896DBBDE');
        $this->addSql('DROP INDEX IDX_8F3F68C5896DBBDE ON log');
        $this->addSql('ALTER TABLE log DROP updated_by_id');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \'\' NOT NULL');
    }
}
