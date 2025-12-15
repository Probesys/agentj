<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20251217082123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add foreign key between quarantine and msgs tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM quarantine WHERE mail_id NOT IN (SELECT mail_id FROM msgs)');
        $this->addSql('ALTER TABLE quarantine ADD CONSTRAINT FK_A7D46024C8776F01296970D4 FOREIGN KEY (mail_id, partition_tag) REFERENCES msgs (mail_id, partition_tag) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_A7D46024C8776F01296970D4 ON quarantine (mail_id, partition_tag)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quarantine DROP FOREIGN KEY FK_A7D46024C8776F01296970D4');
        $this->addSql('DROP INDEX IDX_A7D46024C8776F01296970D4 ON quarantine');
    }
}
