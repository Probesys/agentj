<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930083919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the message_list_metadata table to store the List-* headers of mailing list messages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE message_list_metadata (
                partition_tag INT NOT NULL,
                mail_id VARBINARY(255) NOT NULL,
                list_id VARCHAR(255) DEFAULT NULL,
                list_unsubscribe LONGTEXT DEFAULT NULL,
                list_unsubscribe_post VARCHAR(255) DEFAULT NULL,
                INDEX IDX_78D4410CC8776F01296970D4 (mail_id, partition_tag),
                INDEX message_list_metadata_idx_list_id (list_id),
                PRIMARY KEY (partition_tag, mail_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
            SQL);
        $this->addSql(<<<SQL
            ALTER TABLE message_list_metadata
                ADD CONSTRAINT FK_78D4410CC8776F01296970D4
                    FOREIGN KEY (mail_id, partition_tag)
                    REFERENCES msgs (mail_id, partition_tag)
                    ON DELETE CASCADE
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message_list_metadata DROP FOREIGN KEY FK_78D4410CC8776F01296970D4');
        $this->addSql('DROP TABLE message_list_metadata');
    }
}
