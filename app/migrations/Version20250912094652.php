<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250912094652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove ldap_login_field from connector table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector DROP ldap_login_field');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector ADD ldap_login_field VARCHAR(255) DEFAULT NULL');
    }
}
