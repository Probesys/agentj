<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add LDAP encryption and version fields
 */
// phpcs:disable Squiz.Classes.ValidClassName
// phpcs:disable Generic.Files.LineLength
final class Version20251107100000_add_ldap_encryption_and_version extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add LDAP encryption and version fields to connector table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector ADD ldap_encryption VARCHAR(255) DEFAULT \'none\', ADD ldap_version INT DEFAULT 3');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector DROP ldap_encryption, DROP ldap_version');
    }
}
