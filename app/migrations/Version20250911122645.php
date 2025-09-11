<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250911122645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ldap_shared_with_field to connector table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector ADD ldap_shared_with_field VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector DROP ldap_shared_with_field');
    }
}
