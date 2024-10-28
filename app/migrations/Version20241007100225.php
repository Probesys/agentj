<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241007100225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add processed_user and processed_admin column to out_msgs table with default value false';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE out_msgs ADD processed_user TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE out_msgs ADD processed_admin TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE out_msgs DROP processed_user');
        $this->addSql('ALTER TABLE out_msgs DROP processed_admin');
    }
}