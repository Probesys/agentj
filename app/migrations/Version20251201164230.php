<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201164230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes to msgs and msgrcpt';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX msgrcpt_content_idx ON msgrcpt (content)');
        $this->addSql('CREATE INDEX msgrcpt_ds_status_idx ON msgrcpt (ds, status_id)');
        $this->addSql('CREATE INDEX msgrcpt_bl_status_idx ON msgrcpt (bl, status_id)');
        $this->addSql('CREATE INDEX msgrcpt_status_content_idx ON msgrcpt (status_id, content)');
        $this->addSql('CREATE INDEX msgrcpt_filter_idx ON msgrcpt (rid, bspam_level, ds, bl, content, status_id)');
        $this->addSql('CREATE FULLTEXT INDEX msg_fulltext_idx ON msgs (from_addr, subject, message_id)');
        $this->addSql('CREATE INDEX msgrcpt_content_idx ON out_msgrcpt (content)');
        $this->addSql('CREATE INDEX msgrcpt_ds_status_idx ON out_msgrcpt (ds, status_id)');
        $this->addSql('CREATE INDEX msgrcpt_bl_status_idx ON out_msgrcpt (bl, status_id)');
        $this->addSql('CREATE INDEX msgrcpt_status_content_idx ON out_msgrcpt (status_id, content)');
        $this->addSql('CREATE INDEX msgrcpt_filter_idx ON out_msgrcpt (rid, bspam_level, ds, bl, content, status_id)');
        $this->addSql('CREATE FULLTEXT INDEX msg_fulltext_idx ON out_msgs (from_addr, subject, message_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX msg_fulltext_idx ON msgs');
        $this->addSql('DROP INDEX msg_fulltext_idx ON out_msgs');
        $this->addSql('DROP INDEX msgrcpt_content_idx ON msgrcpt');
        $this->addSql('DROP INDEX msgrcpt_ds_status_idx ON msgrcpt');
        $this->addSql('DROP INDEX msgrcpt_bl_status_idx ON msgrcpt');
        $this->addSql('DROP INDEX msgrcpt_status_content_idx ON msgrcpt');
        $this->addSql('DROP INDEX msgrcpt_filter_idx ON msgrcpt');
        $this->addSql('DROP INDEX msgrcpt_content_idx ON out_msgrcpt');
        $this->addSql('DROP INDEX msgrcpt_ds_status_idx ON out_msgrcpt');
        $this->addSql('DROP INDEX msgrcpt_bl_status_idx ON out_msgrcpt');
        $this->addSql('DROP INDEX msgrcpt_status_content_idx ON out_msgrcpt');
        $this->addSql('DROP INDEX msgrcpt_filter_idx ON out_msgrcpt');
    }
}
