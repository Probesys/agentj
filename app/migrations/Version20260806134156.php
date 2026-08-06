<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806134156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename user.bypass_human_auth to human_authentication_enabled_at and '
            . 'connector.bypass_human_auth to human_authentication_enabled_at';
    }

    public function up(Schema $schema): void
    {
        // Users => change bool bypass_human_auth to datetime human_authentication_enabled_at
        $this->addSql('ALTER TABLE users ADD human_authentication_enabled_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql(<<<SQL
            UPDATE users SET human_authentication_enabled_at = NULL WHERE bypass_human_auth = 1;
        SQL);
        $this->addSql('ALTER TABLE users DROP bypass_human_auth');

        // LdapConnector => rename ldap_bypass_human_auth to ldap_human_authentication_enabled
        $this->addSql(<<<SQL
            ALTER TABLE connector RENAME COLUMN ldap_bypass_human_auth TO ldap_human_authentication_enabled;
        SQL);
        // LdapConnector => toggle value of ldap_human_authentication_enabled because of the rename
        $this->addSql(<<<SQL
            UPDATE connector SET ldap_human_authentication_enabled = NOT ldap_human_authentication_enabled;
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Users =>  restore bool bypass_human_auth
        $this->addSql('ALTER TABLE users ADD bypass_human_auth BOOLEAN DEFAULT FALSE');
        $this->addSql('UPDATE users SET bypass_human_auth = 1 WHERE human_authentication_enabled_at IS NULL');
        $this->addSql('ALTER TABLE users DROP human_authentication_enabled_at');

        // LdapConnector => rename ldap_human_authentication_enabled to ldap_bypass_human_auth
        $this->addSql(<<<SQL
            ALTER TABLE connector RENAME COLUMN ldap_human_authentication_enabled TO ldap_bypass_human_auth;
        SQL);
        // LdapConnector => toggle value of ldap_bypass_human_auth
        // because of the rename of ldap_human_authentication_enabled
        $this->addSql(<<<SQL
            UPDATE connector SET ldap_bypass_human_auth = NOT ldap_bypass_human_auth;
        SQL);
    }
}
