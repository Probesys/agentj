<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303170142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add report_spam_level to domain table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain ADD report_spam_level DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain DROP report_spam_level');
    }
}
