<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250513145658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add spammed and virus message status';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO message_status (id, name) VALUES (6,'spammed'),(7,'virus')");

    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM message_status WHERE id IN (6,7)");

    }
}
