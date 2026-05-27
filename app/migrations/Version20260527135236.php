<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527135236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a colum to track if an import is currently running';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE connector ADD import_started_at DATETIME
            DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)';
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE connector DROP import_started_at;
        SQL);
    }
}
