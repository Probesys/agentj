<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20251121133821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the human_authentication_stylesheet column to domain table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain ADD human_authentication_stylesheet LONGTEXT DEFAULT \'\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain DROP human_authentication_stylesheet');
    }
}
