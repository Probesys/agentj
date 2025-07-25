<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250725133600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename some index, update table domain and out_msgrcpt';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgrcpt
            ADD CONSTRAINT FK_26B7C6D7C8776F01296970D4 FOREIGN KEY (mail_id, partition_tag)
            REFERENCES out_msgs (mail_id, partition_tag) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_26B7C6D7C8776F01296970D4 ON out_msgrcpt (mail_id, partition_tag)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgrcpt RENAME INDEX msgrcpt_idx_mail_id TO out_msgrcpt_idx_mail_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgrcpt RENAME INDEX msgrcpt_idx_rid TO out_msgrcpt_idx_rid
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgrcpt RENAME INDEX idx_2259f7d46bf700bd TO out_msgrcpt_idx_status_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs RENAME INDEX msgs_idx_sid TO IDX_8402A10E57167AB4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs RENAME INDEX msgs_idx_mail_id TO out_msgs_idx_mail_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs RENAME INDEX msgs_idx_time_iso TO out_msgs_idx_time_iso
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs RENAME INDEX msgs_idx_time_num TO out_msgs_idx_time_num
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs RENAME INDEX idx_5d0ffb2d6bf700bd TO out_msgs_idx_status_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs RENAME INDEX msgs_idx_mess_id TO out_msgs_idx_message_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs RENAME INDEX out_msgs_idx_mail_id TO msgs_idx_mail_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs RENAME INDEX idx_8402a10e57167ab4 TO msgs_idx_sid
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs RENAME INDEX out_msgs_idx_time_num TO msgs_idx_time_num
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs RENAME INDEX out_msgs_idx_status_id TO IDX_5D0FFB2D6BF700BD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs RENAME INDEX out_msgs_idx_message_id TO msgs_idx_mess_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs RENAME INDEX out_msgs_idx_time_iso TO msgs_idx_time_iso
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgrcpt DROP FOREIGN KEY FK_26B7C6D7C8776F01296970D4
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_26B7C6D7C8776F01296970D4 ON out_msgrcpt
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgrcpt RENAME INDEX out_msgrcpt_idx_rid TO msgrcpt_idx_rid
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgrcpt RENAME INDEX out_msgrcpt_idx_status_id TO IDX_2259F7D46BF700BD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgrcpt RENAME INDEX out_msgrcpt_idx_mail_id TO msgrcpt_idx_mail_id
        SQL);
    }
}
