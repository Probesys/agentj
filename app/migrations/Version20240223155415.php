<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
// phpcs:disable Generic.Files.LineLength
final class Version20240223155415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add out (sending) policy for users and amavis tables for outgoing mails ';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD `out_policy_id` INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E957DAB4D1 FOREIGN KEY (out_policy_id) REFERENCES policy (id)');
        $this->addSql('CREATE INDEX IDX_1483A5E957DAB4D1 ON users (out_policy_id)');
        $this->addSql('CREATE TABLE out_quarantine (partition_tag INT NOT NULL, mail_id VARBINARY(255) NOT NULL, chunk_ind INT UNSIGNED NOT NULL, mail_text BLOB NOT NULL, PRIMARY KEY(partition_tag, mail_id, chunk_ind)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE out_msgs (partition_tag INT NOT NULL, mail_id VARBINARY(255) NOT NULL, status_id INT DEFAULT NULL, sid BIGINT UNSIGNED DEFAULT NULL, secret_id VARBINARY(255) DEFAULT NULL, am_id VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, time_num INT UNSIGNED NOT NULL, time_iso CHAR(16) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, policy VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, client_addr VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, size INT UNSIGNED NOT NULL, originating CHAR(1) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_unicode_ci`, content CHAR(1) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, quar_type CHAR(1) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, quar_loc VARBINARY(255) DEFAULT NULL, dsn_sent CHAR(1) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, spam_level DOUBLE PRECISION DEFAULT NULL, message_id VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, from_addr VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, subject VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, host VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, validate_captcha INT UNSIGNED DEFAULT 0, send_captcha INT UNSIGNED DEFAULT 0 NOT NULL, message_error LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_mlist TINYINT(1) DEFAULT NULL, INDEX msgs_idx_sid (sid), INDEX IDX_5D0FFB2D6BF700BD (status_id), INDEX msgs_idx_time_iso (time_iso), INDEX msgs_idx_mail_id (mail_id), INDEX msgs_idx_time_num (time_num), INDEX msgs_idx_mess_id (message_id), PRIMARY KEY(partition_tag, mail_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE out_msgrcpt (partition_tag INT NOT NULL, mail_id VARBINARY(255) NOT NULL, rseqnum INT NOT NULL, rid BIGINT UNSIGNED DEFAULT NULL, status_id INT DEFAULT NULL, is_local CHAR(1) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content CHAR(1) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ds CHAR(1) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, rs CHAR(1) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, bl CHAR(1) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, wl CHAR(1) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, bspam_level DOUBLE PRECISION DEFAULT NULL, smtp_resp VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, send_captcha INT UNSIGNED DEFAULT 0 NOT NULL, amavis_output VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX msgrcpt_idx_mail_id (mail_id), INDEX msgrcpt_idx_rid (rid), INDEX IDX_2259F7D46BF700BD (status_id), PRIMARY KEY(partition_tag, mail_id, rseqnum)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE out_wblist (rid INT UNSIGNED NOT NULL, sid INT UNSIGNED NOT NULL, priority INT NOT NULL, group_id INT DEFAULT NULL, wb VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, datemod DATETIME DEFAULT CURRENT_TIMESTAMP, type INT DEFAULT NULL, INDEX IDX_219B9FF3FE54D947 (group_id), INDEX IDX_219B9FF356D41083 (rid), INDEX IDX_219B9FF357167AB4 (sid), PRIMARY KEY(rid, sid, priority)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE out_msgs ADD CONSTRAINT FK_5D0FFB2D6BF700BD_copy FOREIGN KEY (status_id) REFERENCES message_status (id)');
        $this->addSql('ALTER TABLE out_msgs ADD CONSTRAINT FK_5D0FFB2D57167AB4_copy FOREIGN KEY (sid) REFERENCES maddr (id)');
        $this->addSql('ALTER TABLE out_msgrcpt ADD CONSTRAINT FK_2259F7D456D41083_copy FOREIGN KEY (rid) REFERENCES maddr (id)');
        $this->addSql('ALTER TABLE out_msgrcpt ADD CONSTRAINT FK_2259F7D46BF700BD_copy FOREIGN KEY (status_id) REFERENCES message_status (id)');
        $this->addSql('ALTER TABLE out_wblist ADD CONSTRAINT FK_219B9FF357167AB4_copy FOREIGN KEY (sid) REFERENCES mailaddr (id)');
        $this->addSql('ALTER TABLE out_wblist ADD CONSTRAINT FK_219B9FF3FE54D947_copy FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE out_wblist ADD CONSTRAINT FK_219B9FF356D41083_copy FOREIGN KEY (rid) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E957DAB4D1');
        $this->addSql('DROP INDEX IDX_1483A5E957DAB4D1 ON users');
        $this->addSql('ALTER TABLE out_msgs DROP FOREIGN KEY FK_5D0FFB2D6BF700BD_copy');
        $this->addSql('ALTER TABLE out_msgs DROP FOREIGN KEY FK_5D0FFB2D57167AB4_copy');
        $this->addSql('ALTER TABLE out_msgrcpt DROP FOREIGN KEY FK_2259F7D456D41083_copy');
        $this->addSql('ALTER TABLE out_msgrcpt DROP FOREIGN KEY FK_2259F7D46BF700BD_copy');
        $this->addSql('ALTER TABLE out_wblist DROP FOREIGN KEY FK_219B9FF357167AB4_copy');
        $this->addSql('ALTER TABLE out_wblist DROP FOREIGN KEY FK_219B9FF3FE54D947_copy');
        $this->addSql('ALTER TABLE out_wblist DROP FOREIGN KEY FK_219B9FF356D41083_copy');
        $this->addSql('DROP TABLE out_quarantine');
        $this->addSql('DROP TABLE out_msgs');
        $this->addSql('DROP TABLE out_msgrcpt');
        $this->addSql('DROP TABLE out_wblist');
    }
}
