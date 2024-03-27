-- MariaDB dump 10.19  Distrib 10.11.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: agentj_outmail
-- ------------------------------------------------------
-- Server version	10.8.3-MariaDB-1:10.8.3+maria~jammy-log

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
-- Table structure for table `connector`
--

DROP TABLE IF EXISTS `connector`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `connector` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) NOT NULL,
  `created_by_id` int(10) unsigned DEFAULT NULL,
  `updated_by_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenant` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_secret` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ldap_host` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ldap_port` int(11) DEFAULT NULL,
  `ldap_base_dn` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ldap_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ldap_login_field` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ldap_real_name_field` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ldap_email_field` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ldap_bind_dn` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ldap_group_member_field` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ldap_user_filter` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ldap_group_filter` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ldap_group_name_field` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `synchronize_group` tinyint(1) DEFAULT NULL,
  `allow_anonymous_bind` tinyint(1) DEFAULT NULL,
  `ldap_alias_field` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_148C456E115F0EE5` (`domain_id`),
  KEY `IDX_148C456EB03A8386` (`created_by_id`),
  KEY `IDX_148C456E896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_148C456E115F0EE5` FOREIGN KEY (`domain_id`) REFERENCES `domain` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_148C456E896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_148C456EB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `connector`
--

LOCK TABLES `connector` WRITE;
/*!40000 ALTER TABLE `connector` DISABLE KEYS */;
/*!40000 ALTER TABLE `connector` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_stat`
--

DROP TABLE IF EXISTS `daily_stat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_stat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `nb_untreated` int(11) DEFAULT NULL,
  `nb_spam` int(11) DEFAULT NULL,
  `nb_virus` int(11) DEFAULT NULL,
  `nb_authorized` int(11) DEFAULT NULL,
  `nb_banned` int(11) DEFAULT NULL,
  `nb_deleted` int(11) DEFAULT NULL,
  `nb_restored` int(11) DEFAULT NULL,
  `domain_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_64BEE0B4115F0EE5` (`domain_id`),
  CONSTRAINT `FK_64BEE0B4115F0EE5` FOREIGN KEY (`domain_id`) REFERENCES `domain` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_stat`
--

LOCK TABLES `daily_stat` WRITE;
/*!40000 ALTER TABLE `daily_stat` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_stat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dkim`
--

DROP TABLE IF EXISTS `dkim`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dkim` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `selector` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `private_key` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_key` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dkim_idx_domain` (`domain_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dkim`
--

LOCK TABLES `dkim` WRITE;
/*!40000 ALTER TABLE `dkim` DISABLE KEYS */;
INSERT INTO `dkim` VALUES
(1,'laissepasser.fr','agentj','-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCktx1nYVQ6gVeU\nJOqj3MS5y8WOlSXFqZX7zYcVT7KQlgbtKEsEGo1Nd4ikyXiBV5tg/nmuuwVbq4su\nYOgkkyNJqMlEHnXaLbYuUPhq9HpSyo9iGJuzt3RZe+0ZUeDpWra1JgU6PIN2E/S5\nKHhYZUyyLi/V1NLdOasG+IEcebFdp6uEkEAgOpPJcaEgwag+7gtYPKHdczRQt3kN\nRMhgmhEDEFlWOZ5OsZHRf/9UQQm7LQekmaAG4C3MQRpY3UnvySylV1RHLLketn4x\nzOTmLHFYXoUtrOhI2t1+9eWENcwTut8ZTdn/ylftkx+Zp3T1184p9P0FeH2LK/uJ\nKxzNGaFlAgMBAAECggEAChdJzt6QmXD5G5NTaKyKxNMuDDko8j4ceSuzPeP/DYqT\nbA4O4nZ596f+EBJjtpDWbQ/KBqFgh6gvw6yeLDdzbvzPL8Qp9i7cvWOqhGrr34yX\nUiwtxcnUb/L+qJFylPlAvkJRCatRZnd8g6duEARMuZHIznHWZUU2LBwnVMZDznfw\n7BIxbg4glBMqYBrOmJnv8QBBWmywTFgKnemWxkSJmY0Oz2dFzn3TNc3af95l0y/Y\nAOzlxffM/Makshx+KDTByUdhvNkVjvyDu9XEoXGZHRmEEb6goxDI3dTup2yLpOa9\n5hugqsM/LxXQLX5ZRbaACafAasUUj3H79AUxefNiFQKBgQDiGKzQ7M9AHEGya27j\nvqx56pPX7pPeRy6TFPvuAJjdF6oJBGx6ku1sCkE/yAlsD7MGWYUWt/PN9M6PP+Hp\nc/O5qq8odveshgZ32Et54+qDs/rShflhWi4xAkD3109InAwXNrj7U5PxzImc//jL\nJPEAo6rFowtwM6jN2apZuv5+cwKBgQC6gCgzUER0wWpvrepYp8D902jAEhDVC//x\nQiubUxroKnsj+e77tOdMgTtJKVSlifWgEpgods0SFI3NjVVj4dtQzQ6iRDMcRvED\n1zXElFG0mG0PWMjc1nt0ZunRqWGBBB+WKUJAAr3UmwMNtQCMGXnZ8rJlpEJgAZd+\nJspPdonSxwKBgQDDndG3mYvezhPRSL5ScWBMwzZS9uocwL19KASocP/RGm5VCuzI\nSGL+vQijJHFye0rLTZymgVWBdjNZIU5Wa6oNk62kdvnm3Lav7gWvWhBHDusORjfz\nkNCyOl73j+Pa0JhtZT06xZ8U74CIw7cAG+AgS3qvMFfvJeMA5zhKFk2mHQKBgQC0\nEEY//NCLVa7Y8rZfMGAx8Wqifw7HU5WiLrwBdER3QYSw4H+vgCMNh3xhD8mNJl0D\nVtCXyNQID9Y1Tq7SB9+C7OijHGuocooCpkdga79TORObDKKqLV72rOI1rm7YbM2+\nYkl3me4EFkHlewtzOKmgFyXM4sG/BeppPzR3ckBxQwKBgGR2BycVU8MBfrHR73bX\nwtUm5ck59ljYSz/2ui3xT8fnifMuLV8zFBYfD6n7mC5XEve9rTE0QRG9JpjXa6ym\nS7sBW87yrXX7gAu7xXM7wiDgWp6DJ2HkMC/PNN7U28ybdLcgpGo7y9LE1VuG9LX6\nwhQ4OR0V/a1eO9pWJ+WmAy30\n-----END PRIVATE KEY-----\n','-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApLcdZ2FUOoFXlCTqo9zE\nucvFjpUlxamV+82HFU+ykJYG7ShLBBqNTXeIpMl4gVebYP55rrsFW6uLLmDoJJMj\nSajJRB512i22LlD4avR6UsqPYhibs7d0WXvtGVHg6Vq2tSYFOjyDdhP0uSh4WGVM\nsi4v1dTS3TmrBviBHHmxXaerhJBAIDqTyXGhIMGoPu4LWDyh3XM0ULd5DUTIYJoR\nAxBZVjmeTrGR0X//VEEJuy0HpJmgBuAtzEEaWN1J78kspVdURyy5HrZ+Mczk5ixx\nWF6FLazoSNrdfvXlhDXME7rfGU3Z/8pX7ZMfmad09dfOKfT9BXh9iyv7iSsczRmh\nZQIDAQAB\n-----END PUBLIC KEY-----\n'),
(2,'blocnormal.fr','agentj','-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC3u4CbSuQ50wY0\nXxkqCTIMnUYkvN/SnqGm5AVsKP1PvdUp889S3kXxi2Y3bwyUpz5GlexZOI+T3wWW\n8NGvXHnG3Xh9GTAn/cx+Ee7wLHf1cq6IcU9EZsKxnyhG1bwdq3HyVndclkv94elv\nzBDwJjPPdk41jpGUa/FbRG4KvPF6MOFPrXl7WBCsX3TvRyYvSHYemCSwt1S2OX5g\nbhzSKdSS8MdpNMHIY/p318506bzo6Bo49WKNITz8eZG1HYq6GrYygNSR2+trPfl0\nna1qxqLmkjouhVJnPQzRMDrRvsnQrUadG5F8GnjGcg6oaqUkZw6TDMAZmfEjC+/E\nOW5JcVbTAgMBAAECggEAAsEcPB4kCACNH/5lxU9aYVL2ygerF09Ii+Xfl7yiGrcw\n8zbJViOPBpnlx6bUD8leDZC0VmlFY5y0fgNhyvBVwr+izYEHOoRh1c+Xabq3VJcb\nVLWA0Br2Q9OJtve7j+C0BVR66CJ8qyar6UTwfN1qA2v6AaNmGysIsMSSUPFtYDzw\nLd5R4q+Zsy2OxxmklgGSkct4df/E1gqL1Yy1j5UgBBlfz0v8GuNs9STzR7TI3tiH\nMKbUKOJ1Juj2GcW1dJCIoYrnktaGRcpsbRAPJ4qstOgbe5pBvIx7r/StxQG9YGCY\nXUY3c1fCSHp7B+guYlZwctHmKP6yHUOM91RV1BNf8QKBgQDgbh9xgkhCssPxX5/E\nqdjFZqQXcev+KOkoN6BPPviShWFCMGUkTEA+GC6JUo88RLpjQtnACZWaZmdzieRO\nvWLOsQ1MYvfMDhJTQb2sKpAUpYNyZ1nxEyvuvkufevyedYyqNNaIwMBrxVv/OT92\noVExp8rOmZdpdS6FxdW8wvo/KQKBgQDRk9PSzEqBddlVE/cXrkCNFM8+s1AUeBuu\nmyxuHQuqwrFveNNQlKsh9/GzpxZys5859pJzfr8YuBbA0NI3NVWIOM3d7NrVtu/5\ngY/ZeG+tVg5QaK+FkDfl5wWmI43nVZxYGlRx0jFGE4+uv8kUdX64KdM5aO0iiXSJ\nOUTV8yxxmwKBgE6xzoy6piPdEx/b6+XFuKDfMMKXy6w/JCJZ/vhKmFaoNkZDOfPU\nWOJtEEROkrMLVl/f3QKdWbveFIBK5adGeRMIuiUCvujqNjrDXoK0GfljYAgJfVwu\n4MTCLToqodHeXgiApoklLVOczHld1Yb/Yb0LnO18pb7wu/NkP2IE7EKhAoGBAISq\nfXZ9V9Sn01Hl946IAdjNCXv5aoHqnitjkzaJMG+CG2wjIjFqSwKPv/MnnDAzwGGi\n0oZF/5YYQl8AUXIdUu2Od9M6MjUDPtnEyFizwo4JYbpI6oM0Dg58tbi73QJJ0VR8\nJmR+2C4yB8xpH1LSJctSvqEi6KnqNVkAZbDB5KptAoGARBKZDDKPr8XGHrCB0cKv\ncPD2Ku3mGhQV2BQHBPIWSaZq4qbX5mymbXTowvl4vGTJor2ipkCpgOguTjuRRnio\n8uDKMwrxEkWB3Eg3ujBNw+ZkO5ATtNtUuSrcCZiwofeL2Xuosb6ie8IWatgd84V6\nuqvkfwzu3zCe5v4C02ToBr4=\n-----END PRIVATE KEY-----\n','-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAt7uAm0rkOdMGNF8ZKgky\nDJ1GJLzf0p6hpuQFbCj9T73VKfPPUt5F8YtmN28MlKc+RpXsWTiPk98FlvDRr1x5\nxt14fRkwJ/3MfhHu8Cx39XKuiHFPRGbCsZ8oRtW8Hatx8lZ3XJZL/eHpb8wQ8CYz\nz3ZONY6RlGvxW0RuCrzxejDhT615e1gQrF9070cmL0h2HpgksLdUtjl+YG4c0inU\nkvDHaTTByGP6d9fOdOm86OgaOPVijSE8/HmRtR2Kuhq2MoDUkdvraz35dJ2tasai\n5pI6LoVSZz0M0TA60b7J0K1GnRuRfBp4xnIOqGqlJGcOkwzAGZnxIwvvxDluSXFW\n0wIDAQAB\n-----END PUBLIC KEY-----\n');
/*!40000 ALTER TABLE `dkim` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctrine_migration_versions`
--

LOCK TABLES `doctrine_migration_versions` WRITE;
/*!40000 ALTER TABLE `doctrine_migration_versions` DISABLE KEYS */;
INSERT INTO `doctrine_migration_versions` VALUES
('DoctrineMigrations\\Version20211221152434','2024-03-27 10:47:51',4337),
('DoctrineMigrations\\Version20220315154619','2024-03-27 10:47:55',72),
('DoctrineMigrations\\Version20220315160423','2024-03-27 10:47:55',61),
('DoctrineMigrations\\Version20220316163151','2024-03-27 10:47:55',70),
('DoctrineMigrations\\Version20220817140217','2024-03-27 10:47:55',541),
('DoctrineMigrations\\Version20220922122925','2024-03-27 10:47:56',164),
('DoctrineMigrations\\Version20221025152327','2024-03-27 10:47:56',420),
('DoctrineMigrations\\Version20221026082629','2024-03-27 10:47:56',1),
('DoctrineMigrations\\Version20221026092903','2024-03-27 10:47:56',86),
('DoctrineMigrations\\Version20221110134715','2024-03-27 10:47:57',304),
('DoctrineMigrations\\Version20221124105003','2024-03-27 10:47:57',209),
('DoctrineMigrations\\Version20221124140959','2024-03-27 10:47:57',230),
('DoctrineMigrations\\Version20221125153425','2024-03-27 10:47:57',73),
('DoctrineMigrations\\Version20221128110154','2024-03-27 10:47:57',66),
('DoctrineMigrations\\Version20221128153639','2024-03-27 10:47:57',79),
('DoctrineMigrations\\Version20221130081548','2024-03-27 10:47:58',68),
('DoctrineMigrations\\Version20221130093308','2024-03-27 10:47:58',68),
('DoctrineMigrations\\Version20221130101606','2024-03-27 10:47:58',69),
('DoctrineMigrations\\Version20230103105504','2024-03-27 10:47:58',72),
('DoctrineMigrations\\Version20230103130839','2024-03-27 10:47:58',76),
('DoctrineMigrations\\Version20230103131821','2024-03-27 10:47:58',69),
('DoctrineMigrations\\Version20230116083821UpdateImapEcnryptionTypes','2024-03-27 10:47:58',1),
('DoctrineMigrations\\Version20230118091904','2024-03-27 10:47:58',70),
('DoctrineMigrations\\Version20230118130123','2024-03-27 10:47:58',216),
('DoctrineMigrations\\Version20230301132150','2024-03-27 10:47:58',53),
('DoctrineMigrations\\Version20230302085930','2024-03-27 10:47:58',46),
('DoctrineMigrations\\Version20230630095941','2024-03-27 10:47:58',132),
('DoctrineMigrations\\Version20231117134044','2024-03-27 10:47:58',37),
('DoctrineMigrations\\Version20231120143030','2024-03-27 10:47:59',40),
('DoctrineMigrations\\Version20231207083955','2024-03-27 10:47:59',39),
('DoctrineMigrations\\Version20240223155415','2024-03-27 10:47:59',1791),
('DoctrineMigrations\\Version20240327095040','2024-03-27 10:50:43',242);
/*!40000 ALTER TABLE `doctrine_migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `domain`
--

DROP TABLE IF EXISTS `domain`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `domain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `level` double DEFAULT NULL,
  `confirm_captcha_message` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_authentication_sender` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imap_no_validate_cert` tinyint(1) DEFAULT NULL,
  `default_lang` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_port` int(11) DEFAULT NULL,
  `domain_keys_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_A7A91E0BA7A91E0B` (`domain`),
  UNIQUE KEY `UNIQ_A7A91E0BBA7BD17E` (`domain_keys_id`),
  KEY `IDX_A7A91E0B2D29E3C6` (`policy_id`),
  CONSTRAINT `FK_A7A91E0B2D29E3C6` FOREIGN KEY (`policy_id`) REFERENCES `policy` (`id`),
  CONSTRAINT `FK_A7A91E0BBA7BD17E` FOREIGN KEY (`domain_keys_id`) REFERENCES `dkim` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `domain`
--

LOCK TABLES `domain` WRITE;
/*!40000 ALTER TABLE `domain` DISABLE KEYS */;
INSERT INTO `domain` VALUES
(1,2,'laissepasser.fr','smtp.test','smtp.test','2024-03-27 10:51:40',143,'',1,'smtp:[smtp.test]:25','<p><strong>V&eacute;rification de votre email</strong><br /> Merci de valider votre email en validant le captcha ci-dessous</p> ','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">Validation de votre adresse email</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">Bonjour,\n									<p>Vous avez tent&eacute; d&#39;envoyer un mail pour la premi&egrave;re fois &agrave; <strong>[EMAIL_DEST]</strong><br />\n									Cette messagerie est prot&eacute;g&eacute;e par l&#39;anti-spam AgentJ, nous vous demandons de v&eacute;rifier votre email en cliquant sur le lien suivant :</p>\n									</td>\n								</tr>\n								<tr>\n									<td align=\"center\" height=\"76\"><a href=\"[URL_CAPTCHA]\" style=\"           \n                           font-size:16px; ;                                                                                         \n                           -moz-border-radius: 5px;\n                           -webkit-border-radius: 5px;\n                           border-radius: 5px;\n                           line-height: 16px;\n                           background-color: #00a9d4;\n                           text-align: center;\n                           width: 190px;\n                           padding: 5px;\n                           color: #fff;\n                           text-decoration: none;\n                           \">Confirmer mon adresse</a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<p>Cette op&eacute;ration ne sera pas r&eacute;p&eacute;t&eacute;e lors des prochains envois.<br />\n									Merci pour votre compr&eacute;hension.<br />\n									Pour plus d&#39;informations sur la solution anti-spam AgentJ, <a href=\"https://agentj.io/\">cliquez ici</a></p>\n									</td>\n								</tr>\n								<tr>\n									<td>&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"text-align: center;height:100px;color: #3c4858;font-size: 12px;\"><img src=\"https://agentj.io/sites/agentj.io/themes/agentj/motif-agent-j.png\" style=\"width:100%\" /></td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"rnb-col-1\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">RAPPORT AGENTJ</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px; mso-hide: all;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px;  color:#3c4858;\">\n									<p>Bonjour <strong>[USERNAME]</strong>,</p>\n\n									<p>Ci dessous la liste des mails non trait&eacute;s, en attente sur AgentJ</p>\n\n									<p>[LIST_MAIL_MSGS]</p>\n									</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; font-family:Arial,Helvetica,sans-serif, sans-serif; color:#3c4858;\">&nbsp;\n									<p>Merci et bonne journ&eacute;e.</p>\n									</td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n',0.5,'<p>Merci et bonne journ&eacute;e</p> ',NULL,NULL,0,'fr',25,1),
(2,5,'blocnormal.fr','smtp.test','smtp.test','2024-03-27 10:52:34',143,'',1,'smtp:[smtp.test]:25','<p><strong>V&eacute;rification de votre email</strong><br /> Merci de valider votre email en validant le captcha ci-dessous</p> ','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">Validation de votre adresse email</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">Bonjour,\n									<p>Vous avez tent&eacute; d&#39;envoyer un mail pour la premi&egrave;re fois &agrave; <strong>[EMAIL_DEST]</strong><br />\n									Cette messagerie est prot&eacute;g&eacute;e par l&#39;anti-spam AgentJ, nous vous demandons de v&eacute;rifier votre email en cliquant sur le lien suivant :</p>\n									</td>\n								</tr>\n								<tr>\n									<td align=\"center\" height=\"76\"><a href=\"[URL_CAPTCHA]\" style=\"           \n                           font-size:16px; ;                                                                                         \n                           -moz-border-radius: 5px;\n                           -webkit-border-radius: 5px;\n                           border-radius: 5px;\n                           line-height: 16px;\n                           background-color: #00a9d4;\n                           text-align: center;\n                           width: 190px;\n                           padding: 5px;\n                           color: #fff;\n                           text-decoration: none;\n                           \">Confirmer mon adresse</a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<p>Cette op&eacute;ration ne sera pas r&eacute;p&eacute;t&eacute;e lors des prochains envois.<br />\n									Merci pour votre compr&eacute;hension.<br />\n									Pour plus d&#39;informations sur la solution anti-spam AgentJ, <a href=\"https://agentj.io/\">cliquez ici</a></p>\n									</td>\n								</tr>\n								<tr>\n									<td>&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"text-align: center;height:100px;color: #3c4858;font-size: 12px;\"><img src=\"https://agentj.io/sites/agentj.io/themes/agentj/motif-agent-j.png\" style=\"width:100%\" /></td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"rnb-col-1\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">RAPPORT AGENTJ</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px; mso-hide: all;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px;  color:#3c4858;\">\n									<p>Bonjour <strong>[USERNAME]</strong>,</p>\n\n									<p>Ci dessous la liste des mails non trait&eacute;s, en attente sur AgentJ</p>\n\n									<p>[LIST_MAIL_MSGS]</p>\n									</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; font-family:Arial,Helvetica,sans-serif, sans-serif; color:#3c4858;\">&nbsp;\n									<p>Merci et bonne journ&eacute;e.</p>\n									</td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n',0.5,'<p>Merci et bonne journ&eacute;e</p> ',NULL,'noreply@blocnormal.fr',0,'fr',25,2);
/*!40000 ALTER TABLE `domain` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
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
  `priority` int(11) DEFAULT NULL,
  `origin_connector_id` int(11) DEFAULT NULL,
  `uid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ldap_dn` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_F06D3970989D9B62` (`slug`),
  KEY `IDX_F06D39702D29E3C6` (`policy_id`),
  KEY `IDX_F06D3970115F0EE5` (`domain_id`),
  KEY `IDX_F06D39708361673C` (`origin_connector_id`),
  CONSTRAINT `FK_F06D3970115F0EE5` FOREIGN KEY (`domain_id`) REFERENCES `domain` (`id`),
  CONSTRAINT `FK_F06D39702D29E3C6` FOREIGN KEY (`policy_id`) REFERENCES `policy` (`id`),
  CONSTRAINT `FK_F06D39708361673C` FOREIGN KEY (`origin_connector_id`) REFERENCES `connector` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups_wblist`
--

DROP TABLE IF EXISTS `groups_wblist`;
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
-- Dumping data for table `groups_wblist`
--

LOCK TABLES `groups_wblist` WRITE;
/*!40000 ALTER TABLE `groups_wblist` DISABLE KEYS */;
/*!40000 ALTER TABLE `groups_wblist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mailId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated_by_id` int(10) unsigned DEFAULT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8F3F68C5B03A8386` (`created_by_id`),
  KEY `IDX_8F3F68C5896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_8F3F68C5896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_8F3F68C5B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maddr`
--

DROP TABLE IF EXISTS `maddr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maddr` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partition_tag` int(11) DEFAULT NULL,
  `email` varbinary(255) NOT NULL,
  `is_invalid` tinyint(1) DEFAULT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `part_email` (`partition_tag`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maddr`
--

LOCK TABLES `maddr` WRITE;
/*!40000 ALTER TABLE `maddr` DISABLE KEYS */;
/*!40000 ALTER TABLE `maddr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mailaddr`
--

DROP TABLE IF EXISTS `mailaddr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mailaddr` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `priority` int(11) NOT NULL DEFAULT 7,
  `email` varbinary(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mailaddr`
--

LOCK TABLES `mailaddr` WRITE;
/*!40000 ALTER TABLE `mailaddr` DISABLE KEYS */;
INSERT INTO `mailaddr` VALUES
(1,0,'@.');
/*!40000 ALTER TABLE `mailaddr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_status`
--

DROP TABLE IF EXISTS `message_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_status`
--

LOCK TABLES `message_status` WRITE;
/*!40000 ALTER TABLE `message_status` DISABLE KEYS */;
INSERT INTO `message_status` VALUES
(1,'banned'),
(2,'authorized'),
(3,'deleted'),
(4,'Error'),
(5,'restored');
/*!40000 ALTER TABLE `message_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `msgrcpt`
--

DROP TABLE IF EXISTS `msgrcpt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `msgrcpt` (
  `partition_tag` int(11) NOT NULL,
  `mail_id` varbinary(255) NOT NULL,
  `rseqnum` int(11) NOT NULL,
  `rid` bigint(20) unsigned DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `is_local` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ds` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rs` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bl` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wl` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bspam_level` double DEFAULT NULL,
  `smtp_resp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `send_captcha` int(10) unsigned NOT NULL DEFAULT 0,
  `amavis_output` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`partition_tag`,`mail_id`,`rseqnum`),
  KEY `IDX_2259F7D46BF700BD` (`status_id`),
  KEY `msgrcpt_idx_mail_id` (`mail_id`),
  KEY `msgrcpt_idx_rid` (`rid`),
  CONSTRAINT `FK_2259F7D456D41083` FOREIGN KEY (`rid`) REFERENCES `maddr` (`id`),
  CONSTRAINT `FK_2259F7D46BF700BD` FOREIGN KEY (`status_id`) REFERENCES `message_status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `msgrcpt`
--

LOCK TABLES `msgrcpt` WRITE;
/*!40000 ALTER TABLE `msgrcpt` DISABLE KEYS */;
/*!40000 ALTER TABLE `msgrcpt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `msgs`
--

DROP TABLE IF EXISTS `msgs`;
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
  `validate_captcha` int(10) unsigned DEFAULT 0,
  `send_captcha` int(10) unsigned NOT NULL DEFAULT 0,
  `message_error` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
-- Dumping data for table `msgs`
--

LOCK TABLES `msgs` WRITE;
/*!40000 ALTER TABLE `msgs` DISABLE KEYS */;
/*!40000 ALTER TABLE `msgs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `out_msgrcpt`
--

DROP TABLE IF EXISTS `out_msgrcpt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `out_msgrcpt` (
  `partition_tag` int(11) NOT NULL,
  `mail_id` varbinary(255) NOT NULL,
  `rseqnum` int(11) NOT NULL,
  `rid` bigint(20) unsigned DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `is_local` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ds` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rs` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bl` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wl` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bspam_level` double DEFAULT NULL,
  `smtp_resp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `send_captcha` int(10) unsigned NOT NULL DEFAULT 0,
  `amavis_output` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`partition_tag`,`mail_id`,`rseqnum`),
  KEY `msgrcpt_idx_mail_id` (`mail_id`),
  KEY `msgrcpt_idx_rid` (`rid`),
  KEY `IDX_2259F7D46BF700BD` (`status_id`),
  CONSTRAINT `FK_2259F7D456D41083_copy` FOREIGN KEY (`rid`) REFERENCES `maddr` (`id`),
  CONSTRAINT `FK_2259F7D46BF700BD_copy` FOREIGN KEY (`status_id`) REFERENCES `message_status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `out_msgrcpt`
--

LOCK TABLES `out_msgrcpt` WRITE;
/*!40000 ALTER TABLE `out_msgrcpt` DISABLE KEYS */;
/*!40000 ALTER TABLE `out_msgrcpt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `out_msgs`
--

DROP TABLE IF EXISTS `out_msgs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `out_msgs` (
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
  `validate_captcha` int(10) unsigned DEFAULT 0,
  `send_captcha` int(10) unsigned NOT NULL DEFAULT 0,
  `message_error` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_mlist` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`partition_tag`,`mail_id`),
  KEY `msgs_idx_sid` (`sid`),
  KEY `IDX_5D0FFB2D6BF700BD` (`status_id`),
  KEY `msgs_idx_time_iso` (`time_iso`),
  KEY `msgs_idx_mail_id` (`mail_id`),
  KEY `msgs_idx_time_num` (`time_num`),
  KEY `msgs_idx_mess_id` (`message_id`),
  CONSTRAINT `FK_5D0FFB2D57167AB4_copy` FOREIGN KEY (`sid`) REFERENCES `maddr` (`id`),
  CONSTRAINT `FK_5D0FFB2D6BF700BD_copy` FOREIGN KEY (`status_id`) REFERENCES `message_status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `out_msgs`
--

LOCK TABLES `out_msgs` WRITE;
/*!40000 ALTER TABLE `out_msgs` DISABLE KEYS */;
/*!40000 ALTER TABLE `out_msgs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `out_quarantine`
--

DROP TABLE IF EXISTS `out_quarantine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `out_quarantine` (
  `partition_tag` int(11) NOT NULL,
  `mail_id` varbinary(255) NOT NULL,
  `chunk_ind` int(10) unsigned NOT NULL,
  `mail_text` blob NOT NULL,
  PRIMARY KEY (`partition_tag`,`mail_id`,`chunk_ind`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `out_quarantine`
--

LOCK TABLES `out_quarantine` WRITE;
/*!40000 ALTER TABLE `out_quarantine` DISABLE KEYS */;
/*!40000 ALTER TABLE `out_quarantine` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `out_wblist`
--

DROP TABLE IF EXISTS `out_wblist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `out_wblist` (
  `rid` int(10) unsigned NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  `priority` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `wb` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `datemod` datetime DEFAULT current_timestamp(),
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`rid`,`sid`,`priority`),
  KEY `IDX_219B9FF3FE54D947` (`group_id`),
  KEY `IDX_219B9FF356D41083` (`rid`),
  KEY `IDX_219B9FF357167AB4` (`sid`),
  CONSTRAINT `FK_219B9FF356D41083_copy` FOREIGN KEY (`rid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_219B9FF357167AB4_copy` FOREIGN KEY (`sid`) REFERENCES `mailaddr` (`id`),
  CONSTRAINT `FK_219B9FF3FE54D947_copy` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `out_wblist`
--

LOCK TABLES `out_wblist` WRITE;
/*!40000 ALTER TABLE `out_wblist` DISABLE KEYS */;
/*!40000 ALTER TABLE `out_wblist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `policy`
--

DROP TABLE IF EXISTS `policy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `policy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `policy_name` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `virus_lover` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spam_lover` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unchecked_lover` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banned_files_lover` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bad_header_lover` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bypass_virus_checks` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bypass_spam_checks` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bypass_banned_checks` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bypass_header_checks` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `virus_quarantine_to` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spam_quarantine_to` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banned_quarantine_to` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unchecked_quarantine_to` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bad_header_quarantine_to` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clean_quarantine_to` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archive_quarantine_to` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spam_tag_level` double DEFAULT NULL,
  `spam_tag2_level` double DEFAULT NULL,
  `spam_tag3_level` double DEFAULT NULL,
  `spam_kill_level` double DEFAULT NULL,
  `spam_dsn_cutoff_level` double DEFAULT NULL,
  `spam_quarantine_cutoff_level` double DEFAULT NULL,
  `addr_extension_virus` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_extension_spam` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_extension_banned` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_extension_bad_header` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warnvirusrecip` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warnbannedrecip` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warnbadhrecip` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `newvirus_admin` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `virus_admin` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banned_admin` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bad_header_admin` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spam_admin` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spam_subject_tag` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spam_subject_tag2` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spam_subject_tag3` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_size_limit` int(11) DEFAULT NULL,
  `banned_rulenames` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disclaimer_options` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forward_method` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sa_userconf` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sa_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `policy`
--

LOCK TABLES `policy` WRITE;
/*!40000 ALTER TABLE `policy` DISABLE KEYS */;
INSERT INTO `policy` VALUES
(2,'Pas de censure','Y','Y',NULL,NULL,NULL,'N','N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(3,'Accepte les spams','N','Y',NULL,NULL,NULL,'N','N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(4,'Accepte les virus','Y','N',NULL,NULL,NULL,'N','N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(5,'Normale','N','N',NULL,NULL,NULL,'N','N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `policy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quarantine`
--

DROP TABLE IF EXISTS `quarantine`;
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
-- Dumping data for table `quarantine`
--

LOCK TABLES `quarantine` WRITE;
/*!40000 ALTER TABLE `quarantine` DISABLE KEYS */;
/*!40000 ALTER TABLE `quarantine` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rights`
--

DROP TABLE IF EXISTS `rights`;
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
-- Dumping data for table `rights`
--

LOCK TABLES `rights` WRITE;
/*!40000 ALTER TABLE `rights` DISABLE KEYS */;
/*!40000 ALTER TABLE `rights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rights_groups`
--

DROP TABLE IF EXISTS `rights_groups`;
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
-- Dumping data for table `rights_groups`
--

LOCK TABLES `rights_groups` WRITE;
/*!40000 ALTER TABLE `rights_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `rights_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
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
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(9,'default_domain_messages','mail_content_authentification_request','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">Validation de votre adresse email</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">Bonjour,\n									<p>Vous avez tent&eacute; d&#39;envoyer un mail pour la premi&egrave;re fois &agrave; <strong>[EMAIL_DEST]</strong><br />\n									Cette messagerie est prot&eacute;g&eacute;e par l&#39;anti-spam AgentJ, nous vous demandons de v&eacute;rifier votre email en cliquant sur le lien suivant :</p>\n									</td>\n								</tr>\n								<tr>\n									<td align=\"center\" height=\"76\"><a href=\"[URL_CAPTCHA]\" style=\"           \n                           font-size:16px; ;                                                                                         \n                           -moz-border-radius: 5px;\n                           -webkit-border-radius: 5px;\n                           border-radius: 5px;\n                           line-height: 16px;\n                           background-color: #00a9d4;\n                           text-align: center;\n                           width: 190px;\n                           padding: 5px;\n                           color: #fff;\n                           text-decoration: none;\n                           \">Confirmer mon adresse</a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<p>Cette op&eacute;ration ne sera pas r&eacute;p&eacute;t&eacute;e lors des prochains envois.<br />\n									Merci pour votre compr&eacute;hension.<br />\n									Pour plus d&#39;informations sur la solution anti-spam AgentJ, <a href=\"https://agentj.io/\">cliquez ici</a></p>\n									</td>\n								</tr>\n								<tr>\n									<td>&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"text-align: center;height:100px;color: #3c4858;font-size: 12px;\"><img src=\"https://agentj.io/sites/agentj.io/themes/agentj/motif-agent-j.png\" style=\"width:100%\" /></td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n'),
(10,'default_domain_messages','page_content_authentification_request','<p><strong>V&eacute;rification de votre email</strong><br /> Merci de valider votre email en validant le captcha ci-dessous</p> '),
(11,'default_domain_messages','page_content_authentification_valid','<p>Merci et bonne journ&eacute;e</p> '),
(12,'default_domain_messages','mail_content_report','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"rnb-col-1\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">RAPPORT AGENTJ</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px; mso-hide: all;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px;  color:#3c4858;\">\n									<p>Bonjour <strong>[USERNAME]</strong>,</p>\n\n									<p>Ci dessous la liste des mails non trait&eacute;s, en attente sur AgentJ</p>\n\n									<p>[LIST_MAIL_MSGS]</p>\n									</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; font-family:Arial,Helvetica,sans-serif, sans-serif; color:#3c4858;\">&nbsp;\n									<p>Merci et bonne journ&eacute;e.</p>\n									</td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_groups`
--

DROP TABLE IF EXISTS `user_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_groups` (
  `user_id` int(10) unsigned NOT NULL,
  `groups_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`groups_id`),
  KEY `IDX_953F224DA76ED395` (`user_id`),
  KEY `IDX_953F224DF373DCF` (`groups_id`),
  CONSTRAINT `FK_953F224DA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_953F224DF373DCF` FOREIGN KEY (`groups_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_groups`
--

LOCK TABLES `user_groups` WRITE;
/*!40000 ALTER TABLE `user_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_user`
--

DROP TABLE IF EXISTS `user_user`;
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
-- Dumping data for table `user_user`
--

LOCK TABLES `user_user` WRITE;
/*!40000 ALTER TABLE `user_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `policy_id` int(10) unsigned DEFAULT NULL,
  `domain_id` int(11) DEFAULT NULL,
  `original_user_id` int(10) unsigned DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 7,
  `email` varbinary(255) DEFAULT NULL,
  `fullname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `local` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `roles` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emailRecovery` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imapLogin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report` tinyint(1) DEFAULT NULL,
  `date_last_report` int(11) DEFAULT NULL,
  `bypass_human_auth` tinyint(1) DEFAULT NULL,
  `prefered_lang` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origin_connector_id` int(11) DEFAULT NULL,
  `ldap_dn` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office365_principal_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `out_policy_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E9E7927C74` (`email`),
  KEY `IDX_1483A5E92D29E3C6` (`policy_id`),
  KEY `IDX_1483A5E9115F0EE5` (`domain_id`),
  KEY `IDX_1483A5E921EE7D62` (`original_user_id`),
  KEY `IDX_1483A5E98361673C` (`origin_connector_id`),
  KEY `IDX_1483A5E957DAB4D1` (`out_policy_id`),
  CONSTRAINT `FK_1483A5E9115F0EE5` FOREIGN KEY (`domain_id`) REFERENCES `domain` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1483A5E921EE7D62` FOREIGN KEY (`original_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1483A5E92D29E3C6` FOREIGN KEY (`policy_id`) REFERENCES `policy` (`id`),
  CONSTRAINT `FK_1483A5E957DAB4D1` FOREIGN KEY (`out_policy_id`) REFERENCES `policy` (`id`),
  CONSTRAINT `FK_1483A5E98361673C` FOREIGN KEY (`origin_connector_id`) REFERENCES `connector` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,NULL,NULL,NULL,7,NULL,NULL,'admin',NULL,'$2y$13$tXmMtZcqTJvnMfgoHFtS3uOOpL7jSyrkhdX29c53DLMWWux4BVuIu','[\"ROLE_SUPER_ADMIN\"]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(2,2,1,NULL,2,'@laissepasser.fr','Domaine laissepasser.fr',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(3,5,2,NULL,2,'@blocnormal.fr','Domaine blocnormal.fr',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(4,2,1,NULL,7,'user@laissepasser.fr','Accepte Tout','user@laissepasser.fr',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(5,5,2,NULL,7,'user@blocnormal.fr','Refuse par Défaut','user@blocnormal.fr',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_domains`
--

DROP TABLE IF EXISTS `users_domains`;
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
-- Dumping data for table `users_domains`
--

LOCK TABLES `users_domains` WRITE;
/*!40000 ALTER TABLE `users_domains` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wblist`
--

DROP TABLE IF EXISTS `wblist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wblist` (
  `rid` int(10) unsigned NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `wb` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `datemod` datetime DEFAULT current_timestamp(),
  `type` int(11) DEFAULT NULL,
  `priority` int(11) NOT NULL,
  PRIMARY KEY (`rid`,`sid`,`priority`),
  KEY `IDX_219B9FF356D41083` (`rid`),
  KEY `IDX_219B9FF357167AB4` (`sid`),
  KEY `IDX_219B9FF3FE54D947` (`group_id`),
  CONSTRAINT `FK_219B9FF356D41083` FOREIGN KEY (`rid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_219B9FF357167AB4` FOREIGN KEY (`sid`) REFERENCES `mailaddr` (`id`),
  CONSTRAINT `FK_219B9FF3FE54D947` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wblist`
--

LOCK TABLES `wblist` WRITE;
/*!40000 ALTER TABLE `wblist` DISABLE KEYS */;
INSERT INTO `wblist` VALUES
(2,1,NULL,'W','2024-03-27 10:51:40',NULL,0),
(3,1,NULL,'0','2024-03-27 10:52:34',NULL,0);
/*!40000 ALTER TABLE `wblist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-03-27 10:54:22
