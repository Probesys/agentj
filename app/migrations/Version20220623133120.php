<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220623133120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE connector (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, name VARCHAR(255) NOT NULL, active TINYINT(1) DEFAULT NULL, INDEX IDX_148C456E115F0EE5 (domain_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE office365_connector (id INT AUTO_INCREMENT NOT NULL, connector_id INT NOT NULL, tenant VARCHAR(100) NOT NULL, client VARCHAR(100) NOT NULL, client_secret VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_D27A65974D085745 (connector_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE connector ADD CONSTRAINT FK_148C456E115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE office365_connector ADD CONSTRAINT FK_D27A65974D085745 FOREIGN KEY (connector_id) REFERENCES connector (id)');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \' \' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE office365_connector DROP FOREIGN KEY FK_D27A65974D085745');
        $this->addSql('DROP TABLE connector');
        $this->addSql('DROP TABLE office365_connector');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \'\' NOT NULL');
    }
}
