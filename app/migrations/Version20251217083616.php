<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251217083616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fulltext index on (out_)msgs subject columns.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE FULLTEXT INDEX msg_subject_fulltext_idx ON msgs (subject)');
        $this->addSql('CREATE FULLTEXT INDEX msg_subject_fulltext_idx ON out_msgs (subject)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX msg_subject_fulltext_idx ON out_msgs');
        $this->addSql('DROP INDEX msg_subject_fulltext_idx ON msgs');
    }
}
