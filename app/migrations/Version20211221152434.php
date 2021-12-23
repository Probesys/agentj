<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211221152434 extends AbstractMigration
{
    
    public function getDescription(): string
    {
        return '';
    }
    

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS domain (id INT AUTO_INCREMENT NOT NULL, policy_id INT UNSIGNED DEFAULT NULL, domain VARCHAR(255) NOT NULL, srv_smtp VARCHAR(255) NOT NULL, srv_imap VARCHAR(255) NOT NULL, datemod DATETIME DEFAULT CURRENT_TIMESTAMP, imap_port INT DEFAULT 143 NOT NULL, imap_flag VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, transport VARCHAR(255) NOT NULL, message LONGTEXT DEFAULT NULL, mailmessage LONGTEXT DEFAULT NULL, mail_alert LONGTEXT DEFAULT NULL, level DOUBLE PRECISION DEFAULT NULL, confirm_captcha_message LONGTEXT DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, mail_authentication_sender VARCHAR(255) DEFAULT NULL, imap_no_validate_cert TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_A7A91E0BA7A91E0B (domain), INDEX IDX_A7A91E0B2D29E3C6 (policy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS groups (id INT AUTO_INCREMENT NOT NULL, policy_id INT UNSIGNED DEFAULT NULL, domain_id INT NOT NULL, name VARCHAR(255) NOT NULL, datemod DATETIME DEFAULT CURRENT_TIMESTAMP, wb VARCHAR(10) NOT NULL, slug VARCHAR(128) NOT NULL, override_user TINYINT(1) DEFAULT NULL, active TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_F06D3970989D9B62 (slug), INDEX IDX_F06D39702D29E3C6 (policy_id), INDEX IDX_F06D3970115F0EE5 (domain_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS groups_wblist (group_id INT NOT NULL, sid INT UNSIGNED NOT NULL, wb VARCHAR(10) NOT NULL, INDEX IDX_F0715B65FE54D947 (group_id), INDEX IDX_F0715B6557167AB4 (sid), PRIMARY KEY(group_id, sid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS log (id INT AUTO_INCREMENT NOT NULL, created_by_id INT UNSIGNED DEFAULT NULL, action VARCHAR(255) DEFAULT NULL, mailId VARCHAR(255) DEFAULT NULL, details LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, INDEX IDX_8F3F68C5B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS maddr (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, partition_tag INT DEFAULT NULL, email VARBINARY(255) NOT NULL, is_invalid TINYINT(1) DEFAULT NULL, domain VARCHAR(255) NOT NULL, UNIQUE INDEX part_email (partition_tag, email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS mailaddr (id INT UNSIGNED AUTO_INCREMENT NOT NULL, priority INT DEFAULT 7 NOT NULL, email VARBINARY(255) NOT NULL, UNIQUE INDEX email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS message_status (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS msgrcpt (partition_tag INT NOT NULL, mail_id VARBINARY(255) NOT NULL, rseqnum INT NOT NULL, rid BIGINT UNSIGNED DEFAULT NULL, status_id INT DEFAULT NULL, is_local CHAR(1) NOT NULL, content CHAR(1) NOT NULL, ds CHAR(1) NOT NULL, rs CHAR(1) NOT NULL, bl CHAR(1) DEFAULT NULL, wl CHAR(1) DEFAULT NULL, bspam_level DOUBLE PRECISION DEFAULT NULL, smtp_resp VARCHAR(255) DEFAULT NULL, send_captcha INT UNSIGNED DEFAULT 0 NOT NULL, amavis_output VARCHAR(255) DEFAULT NULL, INDEX IDX_2259F7D46BF700BD (status_id), INDEX msgrcpt_idx_mail_id (mail_id), INDEX msgrcpt_idx_rid (rid), PRIMARY KEY(partition_tag, mail_id, rseqnum)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS msgs (partition_tag INT NOT NULL, mail_id VARBINARY(255) NOT NULL, status_id INT DEFAULT NULL, sid BIGINT UNSIGNED DEFAULT NULL, secret_id VARBINARY(255) DEFAULT NULL, am_id VARCHAR(20) NOT NULL, time_num INT UNSIGNED NOT NULL, time_iso CHAR(16) NOT NULL, policy VARCHAR(255) DEFAULT NULL, client_addr VARCHAR(255) DEFAULT NULL, size INT UNSIGNED NOT NULL, originating CHAR(1) DEFAULT \' \' NOT NULL, content CHAR(1) DEFAULT NULL, quar_type CHAR(1) DEFAULT NULL, quar_loc VARBINARY(255) DEFAULT NULL, dsn_sent CHAR(1) DEFAULT NULL, spam_level DOUBLE PRECISION DEFAULT NULL, message_id VARCHAR(255) DEFAULT NULL, from_addr VARCHAR(255) DEFAULT NULL, subject VARCHAR(255) DEFAULT NULL, host VARCHAR(255) NOT NULL, validate_captcha INT UNSIGNED DEFAULT 0, send_captcha INT UNSIGNED DEFAULT 0 NOT NULL, message_error LONGTEXT DEFAULT NULL, is_mlist TINYINT(1) DEFAULT NULL, INDEX IDX_5D0FFB2D6BF700BD (status_id), INDEX msgs_idx_sid (sid), INDEX msgs_idx_mess_id (message_id), INDEX msgs_idx_time_num (time_num), INDEX msgs_idx_time_iso (time_iso), INDEX msgs_idx_mail_id (mail_id), PRIMARY KEY(partition_tag, mail_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS policy (id INT UNSIGNED AUTO_INCREMENT NOT NULL, policy_name VARCHAR(32) DEFAULT NULL, virus_lover CHAR(1) DEFAULT NULL, spam_lover CHAR(1) DEFAULT NULL, unchecked_lover CHAR(1) DEFAULT NULL, banned_files_lover CHAR(1) DEFAULT NULL, bad_header_lover CHAR(1) DEFAULT NULL, bypass_virus_checks CHAR(1) DEFAULT NULL, bypass_spam_checks CHAR(1) DEFAULT NULL, bypass_banned_checks CHAR(1) DEFAULT NULL, bypass_header_checks CHAR(1) DEFAULT NULL, virus_quarantine_to VARCHAR(64) DEFAULT NULL, spam_quarantine_to VARCHAR(64) DEFAULT NULL, banned_quarantine_to VARCHAR(64) DEFAULT NULL, unchecked_quarantine_to VARCHAR(64) DEFAULT NULL, bad_header_quarantine_to VARCHAR(64) DEFAULT NULL, clean_quarantine_to VARCHAR(64) DEFAULT NULL, archive_quarantine_to VARCHAR(64) DEFAULT NULL, spam_tag_level DOUBLE PRECISION DEFAULT NULL, spam_tag2_level DOUBLE PRECISION DEFAULT NULL, spam_tag3_level DOUBLE PRECISION DEFAULT NULL, spam_kill_level DOUBLE PRECISION DEFAULT NULL, spam_dsn_cutoff_level DOUBLE PRECISION DEFAULT NULL, spam_quarantine_cutoff_level DOUBLE PRECISION DEFAULT NULL, addr_extension_virus VARCHAR(64) DEFAULT NULL, addr_extension_spam VARCHAR(64) DEFAULT NULL, addr_extension_banned VARCHAR(64) DEFAULT NULL, addr_extension_bad_header VARCHAR(64) DEFAULT NULL, warnvirusrecip CHAR(1) DEFAULT NULL, warnbannedrecip CHAR(1) DEFAULT NULL, warnbadhrecip CHAR(1) DEFAULT NULL, newvirus_admin VARCHAR(64) DEFAULT NULL, virus_admin VARCHAR(64) DEFAULT NULL, banned_admin VARCHAR(64) DEFAULT NULL, bad_header_admin VARCHAR(64) DEFAULT NULL, spam_admin VARCHAR(64) DEFAULT NULL, spam_subject_tag VARCHAR(64) DEFAULT NULL, spam_subject_tag2 VARCHAR(64) DEFAULT NULL, spam_subject_tag3 VARCHAR(64) DEFAULT NULL, message_size_limit INT DEFAULT NULL, banned_rulenames VARCHAR(64) DEFAULT NULL, disclaimer_options VARCHAR(64) DEFAULT NULL, forward_method VARCHAR(64) DEFAULT NULL, sa_userconf VARCHAR(64) DEFAULT NULL, sa_username VARCHAR(64) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS quarantine (partition_tag INT NOT NULL, mail_id VARBINARY(255) NOT NULL, chunk_ind INT UNSIGNED NOT NULL, mail_text BLOB NOT NULL, PRIMARY KEY(partition_tag, mail_id, chunk_ind)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS rights (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, system_name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS rights_groups (rights_id INT NOT NULL, groups_id INT NOT NULL, INDEX IDX_C05A1BCCB196EE6E (rights_id), INDEX IDX_C05A1BCCF373DCF (groups_id), PRIMARY KEY(rights_id, groups_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS settings (id INT AUTO_INCREMENT NOT NULL, context VARCHAR(50) NOT NULL, name VARCHAR(50) NOT NULL, value LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS users (id INT UNSIGNED AUTO_INCREMENT NOT NULL, policy_id INT UNSIGNED DEFAULT NULL, domain_id INT DEFAULT NULL, groups_id INT DEFAULT NULL, original_user_id INT UNSIGNED DEFAULT NULL, priority INT DEFAULT 7 NOT NULL, email VARBINARY(255) DEFAULT NULL, fullname VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, local CHAR(1) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, roles LONGTEXT DEFAULT NULL, emailRecovery VARCHAR(255) DEFAULT NULL, imapLogin VARCHAR(255) DEFAULT NULL, report TINYINT(1) DEFAULT NULL, date_last_report INT DEFAULT NULL, bypass_human_auth TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), INDEX IDX_1483A5E92D29E3C6 (policy_id), INDEX IDX_1483A5E9115F0EE5 (domain_id), INDEX IDX_1483A5E9F373DCF (groups_id), INDEX IDX_1483A5E921EE7D62 (original_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS users_domains (user_id INT UNSIGNED NOT NULL, domain_id INT NOT NULL, INDEX IDX_7C7BCB57A76ED395 (user_id), INDEX IDX_7C7BCB57115F0EE5 (domain_id), PRIMARY KEY(user_id, domain_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS user_user (user_source INT UNSIGNED NOT NULL, user_target INT UNSIGNED NOT NULL, INDEX IDX_F7129A803AD8644E (user_source), INDEX IDX_F7129A80233D34C1 (user_target), PRIMARY KEY(user_source, user_target)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS wblist (rid INT UNSIGNED NOT NULL, sid INT UNSIGNED NOT NULL, group_id INT DEFAULT NULL, wb VARCHAR(10) NOT NULL, datemod DATETIME DEFAULT CURRENT_TIMESTAMP, type INT DEFAULT NULL, priority INT DEFAULT NULL, INDEX IDX_219B9FF356D41083 (rid), INDEX IDX_219B9FF357167AB4 (sid), INDEX IDX_219B9FF3FE54D947 (group_id), PRIMARY KEY(rid, sid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE domain ADD CONSTRAINT FK_A7A91E0B2D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id)');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D39702D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id)');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE groups_wblist ADD CONSTRAINT FK_F0715B65FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE groups_wblist ADD CONSTRAINT FK_F0715B6557167AB4 FOREIGN KEY (sid) REFERENCES mailaddr (id)');
        $this->addSql('ALTER TABLE log ADD CONSTRAINT FK_8F3F68C5B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE msgrcpt ADD CONSTRAINT FK_2259F7D456D41083 FOREIGN KEY (rid) REFERENCES maddr (id)');
        $this->addSql('ALTER TABLE msgrcpt ADD CONSTRAINT FK_2259F7D46BF700BD FOREIGN KEY (status_id) REFERENCES message_status (id)');
        $this->addSql('ALTER TABLE msgs ADD CONSTRAINT FK_5D0FFB2D6BF700BD FOREIGN KEY (status_id) REFERENCES message_status (id)');
        $this->addSql('ALTER TABLE msgs ADD CONSTRAINT FK_5D0FFB2D57167AB4 FOREIGN KEY (sid) REFERENCES maddr (id)');
        $this->addSql('ALTER TABLE rights_groups ADD CONSTRAINT FK_C05A1BCCB196EE6E FOREIGN KEY (rights_id) REFERENCES rights (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rights_groups ADD CONSTRAINT FK_C05A1BCCF373DCF FOREIGN KEY (groups_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E92D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9F373DCF FOREIGN KEY (groups_id) REFERENCES groups (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E921EE7D62 FOREIGN KEY (original_user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users_domains ADD CONSTRAINT FK_7C7BCB57A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users_domains ADD CONSTRAINT FK_7C7BCB57115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_user ADD CONSTRAINT FK_F7129A803AD8644E FOREIGN KEY (user_source) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_user ADD CONSTRAINT FK_F7129A80233D34C1 FOREIGN KEY (user_target) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wblist ADD CONSTRAINT FK_219B9FF356D41083 FOREIGN KEY (rid) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wblist ADD CONSTRAINT FK_219B9FF357167AB4 FOREIGN KEY (sid) REFERENCES mailaddr (id)');
        $this->addSql('ALTER TABLE wblist ADD CONSTRAINT FK_219B9FF3FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');

        $this->addSql('INSERT INTO `policy` (`id`, `policy_name`, `virus_lover`, `spam_lover`, `unchecked_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `unchecked_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `archive_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_tag3_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `spam_subject_tag3`, `message_size_limit`, `banned_rulenames`, `disclaimer_options`, `forward_method`, `sa_userconf`, `sa_username`) VALUES (2, \'Pas de censure\', \'Y\', \'Y\', NULL, NULL, NULL, \'N\', \'N\', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)');
        $this->addSql('INSERT INTO `policy` (`id`, `policy_name`, `virus_lover`, `spam_lover`, `unchecked_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `unchecked_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `archive_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_tag3_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `spam_subject_tag3`, `message_size_limit`, `banned_rulenames`, `disclaimer_options`, `forward_method`, `sa_userconf`, `sa_username`) VALUES (3, \'Accepte les spams\', \'N\', \'Y\', NULL, NULL, NULL, \'N\', \'N\', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)');
        $this->addSql('INSERT INTO `policy` (`id`, `policy_name`, `virus_lover`, `spam_lover`, `unchecked_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `unchecked_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `archive_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_tag3_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `spam_subject_tag3`, `message_size_limit`, `banned_rulenames`, `disclaimer_options`, `forward_method`, `sa_userconf`, `sa_username`) VALUES (4, \'Accepte les virus\', \'Y\', \'N\', NULL, NULL, NULL, \'N\', \'N\', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)');
        $this->addSql('INSERT INTO `policy` (`id`, `policy_name`, `virus_lover`, `spam_lover`, `unchecked_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `unchecked_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `archive_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_tag3_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `spam_subject_tag3`, `message_size_limit`, `banned_rulenames`, `disclaimer_options`, `forward_method`, `sa_userconf`, `sa_username`) VALUES (5, \'Normale\', \'N\', \'N\', NULL, NULL, NULL, \'N\', \'N\', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)');

        $this->addSql('INSERT INTO `message_status` VALUES (1,\'banned\'),(2,\'authorized\'),(3,\'deleted\'),(4,\'Error\'),(5,\'restored\')');
        
        $this->addSql('INSERT INTO `settings` VALUES (9,\'default_domain_messages\',\'mail_content_authentification_request\',\'<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam Agent-j\"><img alt=\"Solution antispam Agent-j\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam Agent-j\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">Validation de votre adresse email</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">Bonjour,\n									<p>Vous avez tent&eacute; d&#39;envoyer un mail pour la premi&egrave;re fois &agrave; <strong>[EMAIL_DEST]</strong><br />\n									Cette messagerie est prot&eacute;g&eacute;e par l&#39;anti-spam Agent-J, nous vous demandons de v&eacute;rifier votre email en cliquant sur le lien suivant :</p>\n									</td>\n								</tr>\n								<tr>\n									<td align=\"center\" height=\"76\"><a href=\"[URL_CAPTCHA]\" style=\"           \n                           font-size:16px; ;                                                                                         \n                           -moz-border-radius: 5px;\n                           -webkit-border-radius: 5px;\n                           border-radius: 5px;\n                           line-height: 16px;\n                           background-color: #00a9d4;\n                           text-align: center;\n                           width: 190px;\n                           padding: 5px;\n                           color: #fff;\n                           text-decoration: none;\n                           \">Confirmer mon adresse</a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<p>Cette op&eacute;ration ne sera pas r&eacute;p&eacute;t&eacute;e lors des prochains envois.<br />\n									Merci pour votre compr&eacute;hension.<br />\n									Pour plus d&#39;informations sur la solution anti-spam Agent-J, <a href=\"https://agentj.io/\">cliquez ici</a></p>\n									</td>\n								</tr>\n								<tr>\n									<td>&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"text-align: center;height:100px;color: #3c4858;font-size: 12px;\"><img src=\"https://agentj.io/sites/agentj.io/themes/agentj/motif-agent-j.png\" style=\"width:100%\" /></td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n\')');
        $this->addSql('INSERT INTO `settings` VALUES (10,\'default_domain_messages\',\'page_content_authentification_request\',\'<p><strong>V&eacute;rification de votre email</strong><br /> Merci de valider votre email en validant le captcha ci-dessous</p> \')');
        $this->addSql('INSERT INTO `settings` VALUES (11,\'default_domain_messages\',\'page_content_authentification_valid\',\'<p>Merci et bonne journ&eacute;e</p> \')');
        $this->addSql('INSERT INTO `settings` VALUES (12,\'default_domain_messages\',\'mail_content_report\',\'<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"rnb-col-1\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam Agent-j\"><img alt=\"Solution antispam Agent-j\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam Agent-j\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">RAPPORT AGENT-J</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px; mso-hide: all;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px;  color:#3c4858;\">\n									<p>Bonjour <strong>[USERNAME]</strong>,</p>\n\n									<p>Ci dessous la liste des mails non trait&eacute;s, en attente sur Agent-J</p>\n\n									<p>[LIST_MAIL_MSGS]</p>\n									</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; font-family:Arial,Helvetica,sans-serif, sans-serif; color:#3c4858;\">&nbsp;\n									<p>Merci et bonne journ&eacute;e.</p>\n									</td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n\');');
     }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D3970115F0EE5');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9115F0EE5');
        $this->addSql('ALTER TABLE users_domains DROP FOREIGN KEY FK_7C7BCB57115F0EE5');
        $this->addSql('ALTER TABLE groups_wblist DROP FOREIGN KEY FK_F0715B65FE54D947');
        $this->addSql('ALTER TABLE rights_groups DROP FOREIGN KEY FK_C05A1BCCF373DCF');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9F373DCF');
        $this->addSql('ALTER TABLE wblist DROP FOREIGN KEY FK_219B9FF3FE54D947');
        $this->addSql('ALTER TABLE msgrcpt DROP FOREIGN KEY FK_2259F7D456D41083');
        $this->addSql('ALTER TABLE msgs DROP FOREIGN KEY FK_5D0FFB2D57167AB4');
        $this->addSql('ALTER TABLE groups_wblist DROP FOREIGN KEY FK_F0715B6557167AB4');
        $this->addSql('ALTER TABLE wblist DROP FOREIGN KEY FK_219B9FF357167AB4');
        $this->addSql('ALTER TABLE msgrcpt DROP FOREIGN KEY FK_2259F7D46BF700BD');
        $this->addSql('ALTER TABLE msgs DROP FOREIGN KEY FK_5D0FFB2D6BF700BD');
        $this->addSql('ALTER TABLE domain DROP FOREIGN KEY FK_A7A91E0B2D29E3C6');
        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D39702D29E3C6');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E92D29E3C6');
        $this->addSql('ALTER TABLE rights_groups DROP FOREIGN KEY FK_C05A1BCCB196EE6E');
        $this->addSql('ALTER TABLE log DROP FOREIGN KEY FK_8F3F68C5B03A8386');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E921EE7D62');
        $this->addSql('ALTER TABLE users_domains DROP FOREIGN KEY FK_7C7BCB57A76ED395');
        $this->addSql('ALTER TABLE user_user DROP FOREIGN KEY FK_F7129A803AD8644E');
        $this->addSql('ALTER TABLE user_user DROP FOREIGN KEY FK_F7129A80233D34C1');
        $this->addSql('ALTER TABLE wblist DROP FOREIGN KEY FK_219B9FF356D41083');
        $this->addSql('DROP TABLE domain');
        $this->addSql('DROP TABLE groups');
        $this->addSql('DROP TABLE groups_wblist');
        $this->addSql('DROP TABLE log');
        $this->addSql('DROP TABLE maddr');
        $this->addSql('DROP TABLE mailaddr');
        $this->addSql('DROP TABLE message_status');
        $this->addSql('DROP TABLE msgrcpt');
        $this->addSql('DROP TABLE msgs');
        $this->addSql('DROP TABLE policy');
        $this->addSql('DROP TABLE quarantine');
        $this->addSql('DROP TABLE rights');
        $this->addSql('DROP TABLE rights_groups');
        $this->addSql('DROP TABLE settings');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE users_domains');
        $this->addSql('DROP TABLE user_user');
        $this->addSql('DROP TABLE wblist');
    }
}
