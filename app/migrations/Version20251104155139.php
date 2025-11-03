<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251104155139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add amavis_release_at column to msgrcpt table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE msgrcpt ADD amavis_release_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE msgrcpt DROP amavis_release_at');
    }
}
