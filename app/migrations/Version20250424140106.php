<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250424140106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add foreign composite key on msgrcpt table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE msgrcpt ADD CONSTRAINT FK_2259F7D4C8776F01296970D4 FOREIGN KEY (mail_id, partition_tag) REFERENCES msgs (mail_id, partition_tag) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_2259F7D4C8776F01296970D4 ON msgrcpt (mail_id, partition_tag)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE msgrcpt DROP FOREIGN KEY FK_2259F7D4C8776F01296970D4');
        $this->addSql('DROP INDEX IDX_2259F7D4C8776F01296970D4 ON msgrcpt');
    }
}
