<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250911094946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename Connector client into client_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector CHANGE client client_id VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector CHANGE client_id client VARCHAR(100) DEFAULT NULL');
    }
}
