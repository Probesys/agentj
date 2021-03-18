-- MySQL dump 10.17  Distrib 10.3.24-MariaDB, for debian-linux-gnu (x86_64)
-- ------------------------------------------------------
-- Server version	10.3.24-MariaDB-2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ajconfiguration`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ajconfiguration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `captcha`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `captcha` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `domain`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `domain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `captcha_id` int(11) DEFAULT NULL,
  `policy_id` int(10) unsigned DEFAULT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `srv_smtp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `srv_imap` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `datemod` datetime DEFAULT current_timestamp(),
  `imap_port` int(11) NOT NULL DEFAULT 143,
  `imap_flag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL,
  `transport` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mailmessage` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_alert` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confirm_captcha_message` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` double DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_authentication_sender` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imap_no_validate_cert` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_A7A91E0BA7A91E0B` (`domain`),
  KEY `IDX_A7A91E0B1B8DEA76` (`captcha_id`),
  KEY `IDX_A7A91E0B2D29E3C6` (`policy_id`),
  CONSTRAINT `FK_A7A91E0B1B8DEA76` FOREIGN KEY (`captcha_id`) REFERENCES `captcha` (`id`),
  CONSTRAINT `FK_A7A91E0B2D29E3C6` FOREIGN KEY (`policy_id`) REFERENCES `policy` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ext_log_entries`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ext_log_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logged_at` datetime NOT NULL,
  `object_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `object_class` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int(11) NOT NULL,
  `data` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(DC2Type:array)',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `log_class_lookup_idx` (`object_class`),
  KEY `log_date_lookup_idx` (`logged_at`),
  KEY `log_user_lookup_idx` (`username`),
  KEY `log_version_lookup_idx` (`object_id`,`object_class`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_id` int(10) unsigned DEFAULT NULL,
  `domain_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `datemod` datetime DEFAULT current_timestamp(),
  `wb` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `override_user` tinyint(1) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_F06D3970989D9B62` (`slug`),
  KEY `IDX_F06D39702D29E3C6` (`policy_id`),
  KEY `IDX_F06D3970115F0EE5` (`domain_id`),
  CONSTRAINT `FK_F06D3970115F0EE5` FOREIGN KEY (`domain_id`) REFERENCES `domain` (`id`),
  CONSTRAINT `FK_F06D39702D29E3C6` FOREIGN KEY (`policy_id`) REFERENCES `policy` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_wblist`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_wblist` (
  `group_id` int(11) NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  `wb` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`group_id`,`sid`),
  KEY `IDX_F0715B65FE54D947` (`group_id`),
  KEY `IDX_F0715B6557167AB4` (`sid`),
  CONSTRAINT `FK_F0715B6557167AB4` FOREIGN KEY (`sid`) REFERENCES `mailaddr` (`id`),
  CONSTRAINT `FK_F0715B65FE54D947` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mailId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `details` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8F3F68C5B03A8386` (`created_by_id`),
  CONSTRAINT `FK_8F3F68C5B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=714280 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maddr`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maddr` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partition_tag` int(11) DEFAULT NULL,
  `email` varbinary(255) NOT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_invalid` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `part_email` (`partition_tag`,`email`)
) ENGINE=InnoDB AUTO_INCREMENT=451321 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mailaddr`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mailaddr` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `priority` int(11) NOT NULL DEFAULT 7,
  `email` varbinary(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=36064 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `message_status`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migration_versions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migration_versions` (
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `msgrcpt`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `msgrcpt` (
  `partition_tag` int(11) NOT NULL,
  `mail_id` varbinary(255) NOT NULL,
  `rseqnum` int(11) NOT NULL,
  `rid` bigint(20) unsigned DEFAULT NULL,
  `is_local` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ds` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rs` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bl` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wl` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bspam_level` double DEFAULT NULL,
  `smtp_resp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `send_captcha` int(10) unsigned NOT NULL DEFAULT 0,
  `amavis_output` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`partition_tag`,`mail_id`,`rseqnum`),
  KEY `msgrcpt_idx_mail_id` (`mail_id`),
  KEY `msgrcpt_idx_rid` (`rid`),
  KEY `IDX_2259F7D46BF700BD` (`status_id`),
  CONSTRAINT `FK_2259F7D456D41083` FOREIGN KEY (`rid`) REFERENCES `maddr` (`id`),
  CONSTRAINT `FK_2259F7D46BF700BD` FOREIGN KEY (`status_id`) REFERENCES `message_status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `msgs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `msgs` (
  `partition_tag` int(11) NOT NULL,
  `mail_id` varbinary(255) NOT NULL,
  `status_id` int(11) DEFAULT NULL,
  `sid` bigint(20) unsigned DEFAULT NULL,
  `secret_id` varbinary(255) DEFAULT NULL,
  `am_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_num` int(10) unsigned NOT NULL,
  `time_iso` char(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `policy` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_addr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` int(10) unsigned NOT NULL,
  `originating` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `content` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quar_type` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quar_loc` varbinary(255) DEFAULT NULL,
  `dsn_sent` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spam_level` double DEFAULT NULL,
  `message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_addr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `send_captcha` int(10) unsigned NOT NULL DEFAULT 0,
  `message_error` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validate_captcha` int(10) unsigned DEFAULT 0,
  `is_mlist` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`partition_tag`,`mail_id`),
  KEY `IDX_5D0FFB2D6BF700BD` (`status_id`),
  KEY `msgs_idx_sid` (`sid`),
  KEY `msgs_idx_mess_id` (`message_id`),
  KEY `msgs_idx_time_num` (`time_num`),
  KEY `msgs_idx_time_iso` (`time_iso`),
  KEY `msgs_idx_mail_id` (`mail_id`),
  CONSTRAINT `FK_5D0FFB2D57167AB4` FOREIGN KEY (`sid`) REFERENCES `maddr` (`id`),
  CONSTRAINT `FK_5D0FFB2D6BF700BD` FOREIGN KEY (`status_id`) REFERENCES `message_status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `policy`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `policy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `policy_name` varchar(32) DEFAULT NULL,
  `virus_lover` char(1) DEFAULT NULL,
  `spam_lover` char(1) DEFAULT NULL,
  `unchecked_lover` char(1) DEFAULT NULL,
  `banned_files_lover` char(1) DEFAULT NULL,
  `bad_header_lover` char(1) DEFAULT NULL,
  `bypass_virus_checks` char(1) DEFAULT NULL,
  `bypass_spam_checks` char(1) DEFAULT NULL,
  `bypass_banned_checks` char(1) DEFAULT NULL,
  `bypass_header_checks` char(1) DEFAULT NULL,
  `virus_quarantine_to` varchar(64) DEFAULT NULL,
  `spam_quarantine_to` varchar(64) DEFAULT NULL,
  `banned_quarantine_to` varchar(64) DEFAULT NULL,
  `unchecked_quarantine_to` varchar(64) DEFAULT NULL,
  `bad_header_quarantine_to` varchar(64) DEFAULT NULL,
  `clean_quarantine_to` varchar(64) DEFAULT NULL,
  `archive_quarantine_to` varchar(64) DEFAULT NULL,
  `spam_tag_level` double DEFAULT NULL,
  `spam_tag2_level` double DEFAULT NULL,
  `spam_tag3_level` double DEFAULT NULL,
  `spam_kill_level` double DEFAULT NULL,
  `spam_dsn_cutoff_level` double DEFAULT NULL,
  `spam_quarantine_cutoff_level` double DEFAULT NULL,
  `addr_extension_virus` varchar(64) DEFAULT NULL,
  `addr_extension_spam` varchar(64) DEFAULT NULL,
  `addr_extension_banned` varchar(64) DEFAULT NULL,
  `addr_extension_bad_header` varchar(64) DEFAULT NULL,
  `warnvirusrecip` char(1) DEFAULT NULL,
  `warnbannedrecip` char(1) DEFAULT NULL,
  `warnbadhrecip` char(1) DEFAULT NULL,
  `newvirus_admin` varchar(64) DEFAULT NULL,
  `virus_admin` varchar(64) DEFAULT NULL,
  `banned_admin` varchar(64) DEFAULT NULL,
  `bad_header_admin` varchar(64) DEFAULT NULL,
  `spam_admin` varchar(64) DEFAULT NULL,
  `spam_subject_tag` varchar(64) DEFAULT NULL,
  `spam_subject_tag2` varchar(64) DEFAULT NULL,
  `spam_subject_tag3` varchar(64) DEFAULT NULL,
  `message_size_limit` int(11) DEFAULT NULL,
  `banned_rulenames` varchar(64) DEFAULT NULL,
  `disclaimer_options` varchar(64) DEFAULT NULL,
  `forward_method` varchar(64) DEFAULT NULL,
  `sa_userconf` varchar(64) DEFAULT NULL,
  `sa_username` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quarantine`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quarantine` (
  `partition_tag` int(11) NOT NULL,
  `mail_id` varbinary(255) NOT NULL,
  `chunk_ind` int(10) unsigned NOT NULL,
  `mail_text` blob NOT NULL,
  PRIMARY KEY (`partition_tag`,`mail_id`,`chunk_ind`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rights`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `system_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rights_groups`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rights_groups` (
  `rights_id` int(11) NOT NULL,
  `groups_id` int(11) NOT NULL,
  PRIMARY KEY (`rights_id`,`groups_id`),
  KEY `IDX_C05A1BCCB196EE6E` (`rights_id`),
  KEY `IDX_C05A1BCCF373DCF` (`groups_id`),
  CONSTRAINT `FK_C05A1BCCB196EE6E` FOREIGN KEY (`rights_id`) REFERENCES `rights` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_C05A1BCCF373DCF` FOREIGN KEY (`groups_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_user`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_user` (
  `user_source` int(10) unsigned NOT NULL,
  `user_target` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_source`,`user_target`),
  KEY `IDX_F7129A803AD8644E` (`user_source`),
  KEY `IDX_F7129A80233D34C1` (`user_target`),
  CONSTRAINT `FK_F7129A80233D34C1` FOREIGN KEY (`user_target`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_F7129A803AD8644E` FOREIGN KEY (`user_source`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `policy_id` int(10) unsigned DEFAULT NULL,
  `domain_id` int(11) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 7,
  `email` varbinary(255) DEFAULT NULL,
  `fullname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `local` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `roles` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `groups_id` int(11) DEFAULT NULL,
  `emailRecovery` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_user_id` int(10) unsigned DEFAULT NULL,
  `imapLogin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report` tinyint(1) DEFAULT NULL,
  `date_last_report` int(11) DEFAULT NULL,
  `bypass_human_auth` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E9E7927C74` (`email`),
  KEY `IDX_1483A5E92D29E3C6` (`policy_id`),
  KEY `IDX_1483A5E9115F0EE5` (`domain_id`),
  KEY `IDX_1483A5E9F373DCF` (`groups_id`),
  KEY `IDX_1483A5E921EE7D62` (`original_user_id`),
  CONSTRAINT `FK_1483A5E9115F0EE5` FOREIGN KEY (`domain_id`) REFERENCES `domain` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1483A5E921EE7D62` FOREIGN KEY (`original_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1483A5E92D29E3C6` FOREIGN KEY (`policy_id`) REFERENCES `policy` (`id`),
  CONSTRAINT `FK_1483A5E9F373DCF` FOREIGN KEY (`groups_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=694 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_domains`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_domains` (
  `user_id` int(10) unsigned NOT NULL,
  `domain_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`domain_id`),
  KEY `IDX_7C7BCB57A76ED395` (`user_id`),
  KEY `IDX_7C7BCB57115F0EE5` (`domain_id`),
  CONSTRAINT `FK_7C7BCB57115F0EE5` FOREIGN KEY (`domain_id`) REFERENCES `domain` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7C7BCB57A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_groups`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_groups` (
  `user_id` int(10) unsigned NOT NULL,
  `groups_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`groups_id`),
  KEY `IDX_FF8AB7E0A76ED395` (`user_id`),
  KEY `IDX_FF8AB7E0F373DCF` (`groups_id`),
  CONSTRAINT `FK_FF8AB7E0A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_FF8AB7E0F373DCF` FOREIGN KEY (`groups_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wblist`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wblist` (
  `rid` int(10) unsigned NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `wb` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `datemod` datetime DEFAULT current_timestamp(),
  `type` int(11) DEFAULT NULL,
  `priority` int(11) DEFAULT NULL,
  PRIMARY KEY (`rid`,`sid`),
  KEY `IDX_219B9FF356D41083` (`rid`),
  KEY `IDX_219B9FF357167AB4` (`sid`),
  KEY `IDX_219B9FF3FE54D947` (`group_id`),
  CONSTRAINT `FK_219B9FF356D41083` FOREIGN KEY (`rid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_219B9FF357167AB4` FOREIGN KEY (`sid`) REFERENCES `mailaddr` (`id`),
  CONSTRAINT `FK_219B9FF3FE54D947` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'agentj'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-03-17 16:49:14



INSERT INTO `policy` (`id`, `policy_name`, `virus_lover`, `spam_lover`, `unchecked_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `unchecked_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `archive_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_tag3_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `spam_subject_tag3`, `message_size_limit`, `banned_rulenames`, `disclaimer_options`, `forward_method`, `sa_userconf`, `sa_username`) VALUES
(2, 'Pas de censure', 'Y', 'Y', NULL, NULL, NULL, 'N', 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Accepte les spams', 'N', 'Y', NULL, NULL, NULL, 'N', 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Accepte les virus', 'Y', 'N', NULL, NULL, NULL, 'N', 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Normale', 'N', 'N', NULL, NULL, NULL, 'N', 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

INSERT INTO `message_status` VALUES (1,'banned'),(2,'authorized'),(3,'deleted'),(4,'Error'),(5,'restored');

INSERT INTO `users` (`id`, `priority`, `email`, `fullname`, `local`, `password`, `roles`, `username`, `policy_id`, `domain_id`, `groups_id`, `emailRecovery`, `original_user_id`, `imapLogin`) VALUES(1, 7, NULL, 'admin', NULL, '$2y$13$kyVaexJirrp/pjvyBwniGuhUSuezZ/XWDvGWLUrsThWGB1Fsy4UO2', '[\"ROLE_SUPER_ADMIN\"]', 'admin', NULL, NULL, NULL, NULL, NULL, NULL);

INSERT INTO `settings` VALUES (9,'default_domain_messages','mail_content_authentification_request','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam Agent-j\"><img alt=\"Solution antispam Agent-j\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam Agent-j\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">Validation de votre addresse email</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">Bonjour,\n									<p>Vous avez tent&eacute; d&#39;envoyer un mail pour la premi&egrave;re fois &agrave; <strong>[EMAIL_DEST]</strong><br />\n									Cette messagerie est prot&eacute;g&eacute;e par l&#39;anti-spam Agent-J, nous vous demandons de v&eacute;rifier votre email en cliquant sur le lien suivant :</p>\n									</td>\n								</tr>\n								<tr>\n									<td align=\"center\" height=\"76\"><a href=\"[URL_CAPTCHA]\" style=\"           \n                           font-size:16px; ;                                                                                         \n                           -moz-border-radius: 5px;\n                           -webkit-border-radius: 5px;\n                           border-radius: 5px;\n                           line-height: 16px;\n                           background-color: #00a9d4;\n                           text-align: center;\n                           width: 190px;\n                           padding: 5px;\n                           color: #fff;\n                           text-decoration: none;\n                           \">Confirmer mon adresse</a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<p>Cette op&eacute;ration ne sera pas r&eacute;p&eacute;t&eacute;e lors des prochains envois.<br />\n									Merci pour votre compr&eacute;hension.<br />\n									Pour plus d&#39;informations sur la solution anti-spam Agent-J, <a href=\"https://agentj.io/\">cliquez ici</a></p>\n									</td>\n								</tr>\n								<tr>\n									<td>&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"text-align: center;height:100px;color: #3c4858;font-size: 12px;\"><img src=\"https://agentj.io/sites/agentj.io/themes/agentj/motif-agent-j.png\" style=\"width:100%\" /></td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n'),(10,'default_domain_messages','page_content_authentification_request','<p><strong>V&eacute;rification de votre email</strong><br /> Merci de valider votre email en validant le captcha ci-dessous</p> '),(11,'default_domain_messages','page_content_authentification_valid','<p>Merci et bonne journ&eacute;e</p> '),(12,'default_domain_messages','mail_content_report','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"rnb-col-1\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam Agent-j\"><img alt=\"Solution antispam Agent-j\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam Agent-j\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">RAPPORT AGENT-J</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px; mso-hide: all;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px;  color:#3c4858;\">\n									<p>Bonjour <strong>[USERNAME]</strong>,</p>\n\n									<p>Ci dessous la liste des mails non trait&eacute;s, en attente sur Agent-J</p>\n\n									<p>[LIST_MAIL_MSGS]</p>\n									</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; font-family:Arial,Helvetica,sans-serif, sans-serif; color:#3c4858;\">&nbsp;\n									<p>Merci et bonne journ&eacute;e.</p>\n									</td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n');
