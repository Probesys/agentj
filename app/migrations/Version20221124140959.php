<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221124140959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groups ADD origin_connector_id INT DEFAULT NULL, ADD uid VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D39708361673C FOREIGN KEY (origin_connector_id) REFERENCES connector (id)');
        $this->addSql('CREATE INDEX IDX_F06D39708361673C ON groups (origin_connector_id)');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \' \' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D39708361673C');
        $this->addSql('DROP INDEX IDX_F06D39708361673C ON groups');
        $this->addSql('ALTER TABLE groups DROP origin_connector_id, DROP uid');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \'\' NOT NULL');
    }
}
