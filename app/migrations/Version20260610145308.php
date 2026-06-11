<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610145308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing migration on domain entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE domain CHANGE report_spam_level report_spam_level DOUBLE PRECISION DEFAULT 0 NOT NULL,
            CHANGE authorized_senders_spam_level authorized_senders_spam_level DOUBLE PRECISION DEFAULT 5 NOT NULL;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE domain CHANGE authorized_senders_spam_level authorized_senders_spam_level DOUBLE PRECISION DEFAULT '5' NOT NULL,
            CHANGE report_spam_level report_spam_level DOUBLE PRECISION DEFAULT '0' NOT NULL;
        SQL);
    }
}
