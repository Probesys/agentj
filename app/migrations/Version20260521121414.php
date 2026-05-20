<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521121414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a toggle to manage LDAP authentication';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector ADD ldap_allow_connection TINYINT(1) DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector DROP ldap_allow_connection');
    }
}
