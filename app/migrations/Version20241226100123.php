<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241226100123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes to maddr.email, msgs.quar_type, and sql_limit_report.mail_id';
    }

    public function up(Schema $schema): void
    {
        // Add index on maddr.email
        $indexNameMaddr = 'idx_maddr_email';
        $this->addSql('CREATE INDEX ' . $indexNameMaddr . ' ON maddr (email)');

        // Add index on msgs.quar_type
        $indexNameMsgs = 'idx_msgs_quar_type';
        $this->addSql('CREATE INDEX ' . $indexNameMsgs . ' ON msgs (quar_type)');
    }

    public function down(Schema $schema): void
    {
        // Remove index on maddr.email
        $indexNameMaddr = 'idx_maddr_email';
        $this->addSql('DROP INDEX ' . $indexNameMaddr . ' ON maddr');

        // Remove index on msgs.quar_type
        $indexNameMsgs = 'idx_msgs_quar_type';
        $this->addSql('DROP INDEX ' . $indexNameMsgs . ' ON msgs');
    }
}
