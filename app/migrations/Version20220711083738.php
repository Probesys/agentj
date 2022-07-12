<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220711083738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE oauth_config (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, tenant VARCHAR(255) NOT NULL, client VARCHAR(255) NOT NULL, tenant_id VARCHAR(255) NOT NULL, client_id VARCHAR(255) NOT NULL, client_secret VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_BEBD0C64115F0EE5 (domain_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE oauth_config ADD CONSTRAINT FK_BEBD0C64115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \' \' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE oauth_config');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \'\' NOT NULL');
    }
}
