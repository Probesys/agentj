<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409153811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate wblist.wb to the new "accept" rule.';
    }

    public function up(Schema $schema): void
    {
        // Type 0 = user authorization
        // Type 1 = human authentication
        // Type 4 = sent email
        // Type 5 = imported file
        $this->addSql(<<<SQL
            UPDATE wblist SET wb = ' '
            WHERE wb = 'W'
            AND type IN (0, 1, 4, 5);
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            UPDATE wblist SET wb = 'W'
            WHERE wb = ' ';
        SQL);
    }
}
