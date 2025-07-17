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
        $conn = $this->connection;

        // Add index on maddr.email
        $indexNameMaddr = 'idx_maddr_email';
        if (!$this->indexExists('maddr', $indexNameMaddr)) {
            $this->addSql('CREATE INDEX ' . $indexNameMaddr . ' ON maddr (email)');
        }

        // Add index on msgs.quar_type
        $indexNameMsgs = 'idx_msgs_quar_type';
        if (!$this->indexExists('msgs', $indexNameMsgs)) {
            $this->addSql('CREATE INDEX ' . $indexNameMsgs . ' ON msgs (quar_type)');
        }
    }

    public function down(Schema $schema): void
    {
        $conn = $this->connection;

        // Remove index on maddr.email
        $indexNameMaddr = 'idx_maddr_email';
        if ($this->indexExists('maddr', $indexNameMaddr)) {
            $this->addSql('DROP INDEX ' . $indexNameMaddr . ' ON maddr');
        }

        // Remove index on msgs.quar_type
        $indexNameMsgs = 'idx_msgs_quar_type';
        if ($this->indexExists('msgs', $indexNameMsgs)) {
            $this->addSql('DROP INDEX ' . $indexNameMsgs . ' ON msgs');
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $conn = $this->connection;
        $sm = $conn->getSchemaManager();

        $indexes = $sm->listTableIndexes($table);

        return array_key_exists($index, $indexes);
    }
}
