<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220817140217 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE connector (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, created_by_id INT UNSIGNED DEFAULT NULL, updated_by_id INT UNSIGNED DEFAULT NULL, name VARCHAR(255) NOT NULL, active TINYINT(1) DEFAULT NULL, type VARCHAR(50) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, discr VARCHAR(255) NOT NULL, tenant VARCHAR(100) DEFAULT NULL, client VARCHAR(100) DEFAULT NULL, client_secret VARCHAR(100) DEFAULT NULL, INDEX IDX_148C456E115F0EE5 (domain_id), INDEX IDX_148C456EB03A8386 (created_by_id), INDEX IDX_148C456E896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE connector ADD CONSTRAINT FK_148C456E115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE connector ADD CONSTRAINT FK_148C456EB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE connector ADD CONSTRAINT FK_148C456E896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log ADD updated_by_id INT UNSIGNED DEFAULT NULL, ADD updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE log ADD CONSTRAINT FK_8F3F68C5896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8F3F68C5896DBBDE ON log (updated_by_id)');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \' \' NOT NULL');
        $this->addSql('ALTER TABLE users ADD uid VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE connector');
        $this->addSql('ALTER TABLE log DROP FOREIGN KEY FK_8F3F68C5896DBBDE');
        $this->addSql('DROP INDEX IDX_8F3F68C5896DBBDE ON log');
        $this->addSql('ALTER TABLE log DROP updated_by_id, DROP updated');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE users DROP uid');
    }
}
