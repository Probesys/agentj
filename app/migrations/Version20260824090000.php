<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill users.local = Y for existing mailboxes on active domains (fixes #537: '
            . 'amavisd-new default SQL lookup misclassifies them as non-local, tripping the '
            . 'open-relay heuristic and silently discarding inbound mail)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            UPDATE users
            SET local = 'Y'
            WHERE domain_id IN (SELECT id FROM domain WHERE active = 1)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            UPDATE users
            SET local = NULL
            WHERE domain_id IN (SELECT id FROM domain WHERE active = 1)
        SQL);
    }
}
