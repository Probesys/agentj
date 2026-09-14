<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add api_key_hash column to domain table, used to authenticate the generic user-import API';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE domain
            ADD api_key_hash VARCHAR(64) DEFAULT NULL,
            ADD UNIQUE INDEX UNIQ_DOMAIN_API_KEY_HASH (api_key_hash)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE domain
            DROP INDEX UNIQ_DOMAIN_API_KEY_HASH,
            DROP api_key_hash
        SQL);
    }
}
