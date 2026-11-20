<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261119100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mark the addresses of the protected domains as local (users.local = Y) for Amavis';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            UPDATE users
            SET local = 'Y'
            WHERE domain_id IS NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            UPDATE users
            SET local = NULL
            WHERE domain_id IS NOT NULL
        SQL);
    }
}
