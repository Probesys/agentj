<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221124105003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \' \' NOT NULL');
        $this->addSql('ALTER TABLE users ADD origin_connector_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E98361673C FOREIGN KEY (origin_connector_id) REFERENCES connector (id)');
        $this->addSql('CREATE INDEX IDX_1483A5E98361673C ON users (origin_connector_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E98361673C');
        $this->addSql('DROP INDEX IDX_1483A5E98361673C ON users');
        $this->addSql('ALTER TABLE users DROP origin_connector_id');
    }
}
