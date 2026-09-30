<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930130803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the unsubscribe_request table to store the requests to unsubscribe from mailing lists';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE unsubscribe_request (
                id INT AUTO_INCREMENT NOT NULL,
                list_id VARCHAR(255) DEFAULT NULL,
                partition_tag INT NOT NULL,
                mail_id VARBINARY(255) NOT NULL,
                rseqnum INT NOT NULL,
                method VARCHAR(16) NOT NULL,
                status VARCHAR(16) NOT NULL,
                created_at DATETIME NOT NULL,
                address_id BIGINT UNSIGNED NOT NULL,
                requested_by_id INT UNSIGNED DEFAULT NULL,
                INDEX IDX_35C70645F5B7AF75 (address_id),
                INDEX IDX_35C706454DA1E751 (requested_by_id),
                INDEX unsubscribe_request_idx_list (address_id, list_id),
                INDEX unsubscribe_request_idx_message (partition_tag, mail_id, rseqnum),
                INDEX unsubscribe_request_idx_created_at (created_at),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE unsubscribe_request
                ADD CONSTRAINT FK_35C70645F5B7AF75
                    FOREIGN KEY (address_id) REFERENCES maddr (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE unsubscribe_request
                ADD CONSTRAINT FK_35C706454DA1E751
                    FOREIGN KEY (requested_by_id) REFERENCES users (id) ON DELETE SET NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE unsubscribe_request DROP FOREIGN KEY FK_35C70645F5B7AF75');
        $this->addSql('ALTER TABLE unsubscribe_request DROP FOREIGN KEY FK_35C706454DA1E751');
        $this->addSql('DROP TABLE unsubscribe_request');
    }
}
