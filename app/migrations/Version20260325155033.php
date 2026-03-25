<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20260325155033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the authorized_senders_spam_level column to domain table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain ADD authorized_senders_spam_level DOUBLE PRECISION DEFAULT \'5\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain DROP authorized_senders_spam_level');
    }
}
