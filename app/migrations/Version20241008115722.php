<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241008115722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ref_user column to alert table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE alert ADD ref_user VARCHAR(255)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE alert DROP ref_user');
    }
}