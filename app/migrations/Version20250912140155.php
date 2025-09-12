<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250912140155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix domain send_user* columns being not null';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE domain
            CHANGE send_user_alerts send_user_alerts TINYINT(1) DEFAULT 0 NOT NULL,
            CHANGE send_user_mail_alerts send_user_mail_alerts TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE domain
            CHANGE send_user_alerts send_user_alerts TINYINT(1) DEFAULT 0,
            CHANGE send_user_mail_alerts send_user_mail_alerts TINYINT(1) DEFAULT 0
        SQL);
    }
}
