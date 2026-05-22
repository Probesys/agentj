<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521141800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set default values to report and bypass_human_auth in users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE users SET report=false WHERE report IS NULL');
        $this->addSql('UPDATE users SET bypass_human_auth=false WHERE bypass_human_auth IS NULL');
        $this->addSql(<<<SQL
            ALTER TABLE users
            CHANGE report report TINYINT(1) DEFAULT 1 NOT NULL,
            CHANGE bypass_human_auth bypass_human_auth TINYINT(1) DEFAULT 0 NOT NULL;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE users
            CHANGE report report TINYINT(1) DEFAULT NULL,
            CHANGE bypass_human_auth bypass_human_auth TINYINT(1) DEFAULT NULL
        SQL);
    }
}
