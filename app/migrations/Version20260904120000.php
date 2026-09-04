<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add show_portal_link_in_report column to domain table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain ADD COLUMN show_portal_link_in_report BOOLEAN DEFAULT TRUE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain DROP COLUMN show_portal_link_in_report');
    }
}
