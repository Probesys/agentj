<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
// phpcs:disable Generic.Files.LineLength
final class Version20221125153425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE connector ADD ldap_host VARCHAR(255) DEFAULT NULL, ADD ldap_port INT DEFAULT NULL, ADD ldap_base_dn VARCHAR(255) DEFAULT NULL, ADD ldap_password VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \' \' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE connector DROP ldap_host, DROP ldap_port, DROP ldap_base_dn, DROP ldap_password');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \'\' NOT NULL');
    }
}
