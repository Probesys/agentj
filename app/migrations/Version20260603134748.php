<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603134748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Doctrine upgrade';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector CHANGE last_synchronized_at last_synchronized_at DATETIME DEFAULT NULL');
        $this->addSql(<<<SQL
            ALTER TABLE domain CHANGE quota quota JSON DEFAULT NULL,
            CHANGE report_spam_level report_spam_level DOUBLE PRECISION DEFAULT 0 NOT NULL,
            CHANGE authorized_senders_spam_level authorized_senders_spam_level DOUBLE PRECISION DEFAULT 5 NOT NULL;
        SQL);
        $this->addSql('ALTER TABLE groups CHANGE quota quota JSON DEFAULT NULL');
        $this->addSql(<<<SQL
            ALTER TABLE msgrcpt CHANGE amavis_release_started_at amavis_release_started_at DATETIME DEFAULT NULL,
             CHANGE amavis_release_ended_at amavis_release_ended_at DATETIME DEFAULT NULL;
        SQL);
        $this->addSql('ALTER TABLE users CHANGE quota quota JSON DEFAULT NULL');
        $this->addSql(<<<SQL
            ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL,
            CHANGE available_at available_at DATETIME NOT NULL,
            CHANGE delivered_at delivered_at DATETIME DEFAULT NULL;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE connector CHANGE last_synchronized_at last_synchronized_at
            DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE domain CHANGE authorized_senders_spam_level authorized_senders_spam_level
            DOUBLE PRECISION DEFAULT \'5\' NOT NULL,
            CHANGE report_spam_level report_spam_level DOUBLE PRECISION
            DEFAULT \'0\' NOT NULL, CHANGE quota quota JSON DEFAULT NULL COMMENT \'(DC2Type:json)\';
        SQL);
        $this->addSql('ALTER TABLE groups CHANGE quota quota JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql(<<<SQL
            ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME
            NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            CHANGE available_at available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\';
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE msgrcpt CHANGE amavis_release_started_at amavis_release_started_at DATETIME
            DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            CHANGE amavis_release_ended_at amavis_release_ended_at DATETIME
            DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\';
        SQL);
        $this->addSql('ALTER TABLE users CHANGE quota quota JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }
}
