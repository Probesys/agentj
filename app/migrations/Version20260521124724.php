<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521124724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add toggles to manage bypass human auth and report sending';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE connector
            ADD ldap_bypass_human_auth TINYINT(1) DEFAULT 0,
            ADD ldap_report TINYINT(1) DEFAULT 1
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE connector
            DROP ldap_bypass_human_auth,
            DROP ldap_report
        SQL);
    }
}
