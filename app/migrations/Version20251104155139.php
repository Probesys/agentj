<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251104155139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add amavis_release_started_at and amavis_release_ended_at columns to msgrcpt table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE msgrcpt
            ADD amavis_release_started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            ADD amavis_release_ended_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE msgrcpt
            DROP amavis_release_started_at,
            DROP amavis_release_ended_at
        ');
    }
}
