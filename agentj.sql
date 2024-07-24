-- MariaDB dump 10.19  Distrib 10.8.3-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: agentj
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `connector`
--

LOCK TABLES `connector` WRITE;
/*!40000 ALTER TABLE `connector` DISABLE KEYS */;
INSERT INTO `connector` VALUES
(1,3,NULL,NULL,'ms365',NULL,'Office365','2024-07-23 09:53:33','2024-07-23 09:53:33','office365','69b7c571-833b-4db3-9fdd-4eee928ac42c','355917bb-1185-478f-905a-b400c74e9d13','E498Q~1tX3Keb.uRM.2k2kOype9cQCwHU7ktlbK4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL),
(2,4,NULL,NULL,'p6-idm2.idm.probesys.net',NULL,'LDAP','2024-07-23 09:55:36','2024-07-23 09:55:36','LDAP',NULL,NULL,NULL,'p6-idm2.idm.probesys.net',389,'cn=users,cn=accounts,dc=idm,dc=probesys,dc=net','MTcyMjMzMzMzNnZ2MHhpZ2Z6dVh1TTc5ODJuZHJTaXc9PQ==','uid','sn','mail','uid=binddn-agentj-dev,cn=sysaccounts,cn=etc,dc=idm,dc=probesys,dc=net',NULL,'(uid=*)',NULL,NULL,0,0,'departmentNumber');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dkim`
--

LOCK TABLES `dkim` WRITE;
/*!40000 ALTER TABLE `dkim` DISABLE KEYS */;
INSERT INTO `dkim` VALUES
(1,'laissepasser.fr','agentj','-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCHz6HVvZCJAxsd\nVBmSS37jiqnMJNAK9zZXJEvifDJsry0JdOyR1/nb2ejpFdEiZ7BQSUWyaja+EBI+\nuXmLpnNcN/IW6mOT0uqEoefq0LhbM7ROnPtBdGTsJW5TuZnmvhEK7Oo2rwafApwE\nGkB5goxujwaBD+Xqm2Q4e9kpnDNX1QyusCLBVJbegAT0zJTamVrwJq5MFcwXySxF\nxPtUEq2hKmuCQmwEaRcLhO2KdBQs5bXh+oyXeHan3hoVhIC1lp8M4ex6jFumZbJw\nyWBLW1h5xitVZTzvJW/VMJUKNPH1AVKM5VDt9gRIDDup8y5MStSh1qwvREBEi86p\nmIiep8PrAgMBAAECggEAJcvcLz79fZQWtLfWEgfevXa2wLCyEYsxXVkloVeNNQAf\nmZUnzYL873mav1uqA1g2EnIB6qBysbyJR1mZAQuG6XBMeKIsqlQ1nkns4EXMON2P\n3Z0Es/evqqTUKorp0PSui4rZt/RH9HLmdqHTtb1mVdKKLdr90qgCYVHeYx0skNG7\nUvidA6azLBqPVhOkP0JdKESmkBQ06AgTz5dYDwgYvqdYcY92DGGP9kHJ3j/7nlw2\nqF5d8vM8kyUcj2aLsSYoudkge30U16Vc3cOD2aLCJQ1utvlIi7SdtZVfA5mxE7XA\nReKSoa8mu6naj0X4j1/pFWVLxQYveMx7Tzq4kcG9YQKBgQC9/EQr+EEz1n5nWmB+\neOB2uKlOG7WU0iAISbcG/Ldf5arkK7W41ry3itsSsPY92oN6YS9iNWSPk+jx1maZ\nYMy6bvPhRVgzAq2oEV2Kusbp+xUjXylTH/lI/kARjqZY/h15EYSlg/HtbhtyfXvc\nBDsIJxkoV9gcKUsaRjccprwtkQKBgQC3AGfk0NsJDNahtO+u3mFQ350rWQbkVLUW\nWKzKBJIsRkFgY9CYyxAXz1odjCRcRnSiGNf9aGQqlceZ2HiaH84s/FZMKDoI/hQx\nWTnfO70Rib5h6XYgc3W8Y4yyPIpHbKrWDFssn+cV1+WqkuDYZz4nGszFCJc1yrc1\nNp6pu8lLuwKBgAlzTwDQ7I1jkg4aFMq1wJSnQuS7xCCPA0DmSdBzKbv/dKy87+Et\n7V1D7vnTC6yv/fJMe0rrVQE/XksJWzkt84Eim8cM6AJBk9nUY07PU62366lCxo93\n+7KB0zYMXoH2wgiPsoV5NsOUwpDDZTHgk/8n0ryLAhkEhNPdwkgOkzXBAoGAYg2S\nnVSW+Atr+SsEfdLjm7yk7vP9sFv+x2Ft+7RlSdm+79GrlCLBlbBhXZGYbeTGW5Aq\nMro7aWHll/YX8KT4CLyP1LB9IBJbaXGgg47zqDEA1F+ODqcuv2krmti7UrfT9Wqb\nc5ad7+NFSJb4aJsw/yzp6OzIcGcUsSt56gIXQj8CgYEAlgpVE6T0nJOIZQg8wCe6\nUv5Jyh0w+YITPCEIRvUK0/0Md2SfPglEP6TVBCu0y5lJoRrofNjrSHJCLT0oZCf3\nTcsMXPIhhtLdZk0vTrEqxRV/bPEIuMNQ1Wf2t45mTBx82XlIR0r8Epg7s3y3vWZ5\nEdtD4LyqgPteMVlb7DX6Qgo=\n-----END PRIVATE KEY-----\n','-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAh8+h1b2QiQMbHVQZkkt+\n44qpzCTQCvc2VyRL4nwybK8tCXTskdf529no6RXRImewUElFsmo2vhASPrl5i6Zz\nXDfyFupjk9LqhKHn6tC4WzO0Tpz7QXRk7CVuU7mZ5r4RCuzqNq8GnwKcBBpAeYKM\nbo8GgQ/l6ptkOHvZKZwzV9UMrrAiwVSW3oAE9MyU2pla8CauTBXMF8ksRcT7VBKt\noSprgkJsBGkXC4TtinQULOW14fqMl3h2p94aFYSAtZafDOHseoxbpmWycMlgS1tY\necYrVWU87yVv1TCVCjTx9QFSjOVQ7fYESAw7qfMuTErUodasL0RARIvOqZiInqfD\n6wIDAQAB\n-----END PUBLIC KEY-----\n'),
(2,'blocnormal.fr','agentj','-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCTswbaX7JGNOu2\nN8GT3+cwae8tv8qn80gtZF0kUJcgaJz0GvN89aFG1205FJKn2c4xE46iBUpxz+lE\nJdLv0zu44JttWeQ6qANh3UonN4S6bz90x19bwXfWZscyzdQiuIiNHfjAK6kqUh8Z\n/svhhLpmt2DmERvj19W1sWQ/gLtifLfiV0dFZcnThLfpg1krSjJaXHmHq16ZWPKQ\nFuv3rPtbjwrjeqV8Bm9CBBtH0FzgWNpYxKxJRhQgIgKGQfhB1vqsUgQ1UczPVebp\nkiMaj4cNVjWA0Wx/rrrB6cncTHS+0fqtNYkOnIPLDH4ZrI2d/QEeGvr3RMcprSqQ\nnlxN7padAgMBAAECggEAAU5tT0rjFL1KikhZUmejWPH0D+9M5N3YJU+umE/n1VWb\nuqKQb/l4P1AAr/BziUjJR75Xn2RO8fUE2Na7bJbqbV8OJ1nXIPgdJk50UGOytmsw\nT4FoEmFd+4mi6yRdpiV+C5omQAgFHVCOgxSA1mbDT3Gf/XWEKUoJPevdEE3qMTuq\nXb1ZTOuGXwM2APj6Ouhk4XhGe4bqQHFFFSNZ1psnSSLZdHtrLn+6F4NlLzbfHvuE\ny/02kkw3lOv2xB1tN1Obvk/7bhmBAfq0ReberXlp4r984/SYOO0SnwfTI9mhj6bA\n6rH3h0jg+aFan/7G0AYLqfmLazz86XjBggeWnL34yQKBgQDF+BF1OyCBo1VCDub5\nM7Igm2WLuIAr6WaakCvh2YrmYp0aBzQz/1b/WD/cgI3afhmgE0P6nD7UEMWOxyo/\no7Px4i2MKyTnBJgrE5BobNK1ZcsFQKIMSrYiPiJqI4QBholsMd5WA3DdNIHAF9w0\n+w3t0u+ujct4h7ctDQXm5dmYRQKBgQC+/qKhuXw0yWjUb9IQ2SUNRMrGdtaVpCpO\nOsUgZccGdwLUic6Ur8L7VvfjTKySJcZg0oTTek0hWLuBm4obhJKk7ekWTQIW/BBs\nyFIy/Wc2dM4wR/EBM+DrtUr57h98zlQ9kXgGJIQ6Koff3VPDB22WCtLGcB5cxxxi\npnKxgLQGeQKBgBX/+livyG3/q7Lamxpof0wWWNONHllC588XZHkFxuTRYqoI0OlS\nX+Widf5YHuuABfL/ERfhky3X60xxn6VQ2OdxfiRMix5NKWfoFa01Irlb5ZLg2uFI\n0DK/+u0Wo03UGKN4az/dxAYhFqY1QJh2qdabEV2xjkIHuvh7sQM22daNAoGBAI0L\nI/SEMpoUxopizWFNyhPjYk9vgQXexrMgqWREZNZ8WqWz4s9ggH/1eHftrl768GWd\n8SLu5Tij+0w6HaI2o8iSPJgQZGOu3z5GLJWlFmxbk8yJxAtcUVQffG/XDn+vKvyQ\niW8X1mHR+Kc0HeAfgyjTc1XBhFqFW4vqWTaF0ewZAoGAIXRq0xk0f6FJ6rzukXTt\ndBk0VrC1WxUUrH4Q76jhPZIekUJH+c86iskN8Tj0FiXjEmLdaQG2UcKi0j5pw74j\n4xQV6x7qnbwtufPMQIBxMOy18eb8z1ppb8Ph94Jt0lmohZpW0g1SJXuFqT9vIlG2\npqBCeThaCPcji3yQEQn+NQk=\n-----END PRIVATE KEY-----\n','-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAk7MG2l+yRjTrtjfBk9/n\nMGnvLb/Kp/NILWRdJFCXIGic9BrzfPWhRtdtORSSp9nOMROOogVKcc/pRCXS79M7\nuOCbbVnkOqgDYd1KJzeEum8/dMdfW8F31mbHMs3UIriIjR34wCupKlIfGf7L4YS6\nZrdg5hEb49fVtbFkP4C7Yny34ldHRWXJ04S36YNZK0oyWlx5h6temVjykBbr96z7\nW48K43qlfAZvQgQbR9Bc4FjaWMSsSUYUICIChkH4Qdb6rFIENVHMz1Xm6ZIjGo+H\nDVY1gNFsf666wenJ3Ex0vtH6rTWJDpyDywx+GayNnf0BHhr690THKa0qkJ5cTe6W\nnQIDAQAB\n-----END PUBLIC KEY-----\n'),
(3,'m365.probesys.com','agentj','-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDG12kD7taWYUv+\nGYd+536loX/rPpIqPnfB/HEB7V8TcB8pH1ES1W9WT5lNJYGRgoEoyprzZiQvTl3s\nEtBG1JgH4v7Jq4/tKvIyBPgBT6atZuZ0OD/93TnvrDKgyz6MLIESf/mU2U+5RejB\n8sZCpHN/G4+FN+7DQ3L0/qJJyXqluDx+dYaZWdWORO90dKQopjxQ1aX3YNjXb5dv\n2ua1Cg6R8SwopyRxME++TCUcAlJVzSzM6FSXjrBgru67wL5FPCdFm/S5eBLID2fD\noIPW9I8zBiKf+F6dDUZLq09IqOIaWI7SJBBsQFu/5go58VYUp9gspo4tw49Ha14F\nChwN5kFFAgMBAAECggEAS5AGp2j3ATS+VTTMaex9E2JZI0Om9gjBJ+XP6CSVkeR0\n4wK0o4yaf9lF76xGHc7TWHYOBlsVeyizxoerwI7/q1eW7XqV+lHsshdzS8RCGflV\nsGrD3qxYXP06TCKQhWJQNoCNjwQ6KLUpa7Lfpumpvm7xznVf9bJHGFU/xPyc8CZ0\nvsVTuf+b8PPqw6knwZJbGcirU89mj+bMf+5OxTgEh2hCb42sBSy0NLTBxbikDMcJ\nC43on+qxCceCb0KMjeUbdBA2mYrB8Mvwh+Oprck4/ohcYSz6FuVXIPbgnJG2VjTy\nW1HLHFhLWSPFkMuOpSjUPQut6jw5zr6xDihO5jEBLQKBgQDoxkQlyJ4dISd7BsbQ\n4oxX+1ccWPwkrQUhdW+JSDmob6DFLmoQqSDHLbVcCYtZ9BWy1BqjSgEAq4P9hMC1\n7n7YouISrB2QJ+EmUGhikzfROcnhozMZMUiJpUk7H4vV/5oHEsYRPg0ZbS2N1wOx\nVSwOvurLge3qddPAdZluJc+PawKBgQDarmVtHbWv7hE8ZC6WOBGurQM38lhiuLnK\nXz+pVJvqqvnyWmssRy9Fctd/WydsRhiTBBHfK37lL07lbVh+/uiML1Y4gGdFmn9i\nikXwbJZXfm2DE4O1ZwE/3omdyumvDIkikWhvl0Ii0tkQMYlA85VOPuSBzQt0SqTD\nsnpMyO8ODwKBgQDbsl2Zh7rr5WHc9O1rM5egZqG2KKp6dy7TgIJwANIijpEf6gnD\nhc1hEFwO7RcwJHbiXNxzZ6KAnmEqQ0SbsejY0Qss8nPInksbnWKYPzi8aZ2IMD9H\nNMOw3ma6vbB+nwsiR+7XUUiNygTnzZs3oRq/JCR5RT1nQCRGjvfsBRXc1wKBgQDX\nrMCjyXVzuhyNr1CzH0FxQXm3J/E8DGxQx3gGDUhpPY+eEsc4uExK9mTLWp+bDdcW\nyCDINiHE78+kPx/UwJuiBt3Gg/1WjHWuPimUYcQ1Lp/bctenhZuZ3mVjlsi6uGZY\nDYku5cN4jPNAIVr5EEPlE+pSimQNocC2scQlDteWyQKBgDYNzRz3/JbXB5xsblUk\njaHlWtMbLP0q3aOlkMxUhEKrl3cvA5N2c+tPwyNSovZZZpz+viRtQ9bIulMMIY6E\nAKy924UYaXDpOsdyVfqG+xw78slLctygHWtN6auUPKMjeoLJpdMEGtDpeeYNLKMr\n+sOWym5+sNh4yexlSaD8/gV/\n-----END PRIVATE KEY-----\n','-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxtdpA+7WlmFL/hmHfud+\npaF/6z6SKj53wfxxAe1fE3AfKR9REtVvVk+ZTSWBkYKBKMqa82YkL05d7BLQRtSY\nB+L+yauP7SryMgT4AU+mrWbmdDg//d0576wyoMs+jCyBEn/5lNlPuUXowfLGQqRz\nfxuPhTfuw0Ny9P6iScl6pbg8fnWGmVnVjkTvdHSkKKY8UNWl92DY12+Xb9rmtQoO\nkfEsKKckcTBPvkwlHAJSVc0szOhUl46wYK7uu8C+RTwnRZv0uXgSyA9nw6CD1vSP\nMwYin/henQ1GS6tPSKjiGliO0iQQbEBbv+YKOfFWFKfYLKaOLcOPR2teBQocDeZB\nRQIDAQAB\n-----END PUBLIC KEY-----\n'),
(4,'probesys.com','agentj','-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC4ShjqPDSPOLCe\nsvwvEKpw/RxdVrVNa86yAs+lmnce82EIXv1IcuoH63NV6VZTYC4jcR09FZxugjKD\nByIfFviikXMdEFp+Jo9R4UzVM8T7kRXIYTUNRiWT9GpRspnbzGP+EfBgVVJ/TgA4\naBoU5hG+dBG8/thjNFT0/jNFVn5GCzzSuuWR54pwBbzdpUHEwxJXAaNJtCxfgrFc\naefu2PBB1bSIkqmxAOJ4rOsporXMJK52dLSJs6QjsfAKpaRRUFSOh+ngkZ31N6Qn\nYxPnshwnr1YT2JN9T94I2f8nQ2K+vdVE4UBD/wWoMdFjRmmHjnmZjPMl9vo/R9h3\n77aJIrI1AgMBAAECggEAICUwnl79DgRl7nCrA2ylRksoMPHIcyY8ahmKbcj5QUlT\nohmrlYER6Gq99fzbBXUP5ze6UWbdH/uO4wMSBRlFKlozmZy6JCoGstIZTFEY6Yaf\npZGrJtMXB+1IxFJiew/2WyF2697GIpWQ/UXPUtjkvHNTisNrLDa7IPbcK5qQMRhF\n70CTZp/Wm5/BKAgvU70eSr+1RtX8/fl5BLhvCLuaKKON1RG6YXiPy7Dlg9OAHPSm\nknZZrv+7GJotYG8V2DxUlpH7gsJjyk9OEQEMFwJvAVVeXa+d6fiiDYKqIIbGOPYK\n4KZqb5X2ZKtqKnrMvvcGSaPRd0Wh9pyLQjKZhTDYMQKBgQDbDsYF/aZOLMGE5beB\nhF51zfAXIomuShoiQgNGMLW1M+MfOzjsSqhwdvYWwRuuysMUuQ4gsVRiLCsO4y2P\nXJ5JPIPTJXFW8ZjpdJ/PiB8A/40FAmOUNaDDiWNyK14spxZEvrQrMii+Xn7KrHIB\nHUanSxDMPY/jpN3nL+GJ5a+a/QKBgQDXXkwsiFMUE7zchrqx+hZjO0J6deX1b0cD\nNopVcRZM6JP31Yo2nY9RBoZO1S8eu6xXPNCIXxvtXXh7ABE1I/GGUrVnnRYe2HbT\nUzcQ0grZDdtyEJ4cXkGg7WzctAjNKKg01nP1Tapi3QvyQmcYr4dcDMzbyMTCGqpl\nZsiRcJKlmQKBgDCu/tlz/tLe5X9IoljZdzjNNO+fUGP4ugglDZ860hdcWbymJ8dz\n7C1acuSptCJLk8F2QyqG3o8W69baLFTxK4hxN4bkimTdOrOfpKi1wtYw9UFIv6K/\nrGPok75a3wojdDKBA3+aHS35yEn4uzl+LSae6zPI9sKL8Hkhzgkcj8NtAoGBAK7X\nwjWhEvlIsNnDk0usAYLb+DNdBazEgz0riRCmd3lGk+Hu/X8rWM9p4Z2cWt2zdM15\n9L3RoOWKLaaFvnKK1Ki8+sK8d9ng2E7lzt2b9+yQ4Gfw77uOFe+k8O0YR9Duwefr\ndkZeoAYU+H7bw8D5t4VWavW/P2Vpqo4Z7JiOcK95AoGATgrM3wgmPqTlppr1AyzA\nmourxPhY7HCd7SqFpu79rgFSriq2mM4fmf2DfP4HAvnN9XM7205zzRIPKCKIfKaB\n5ABo8RLi+OdHrFm8y/WMCKotwuEKSqkVMjrXEFn/NhvVTvKEXcKlpADR+feja4QT\nYgbZMhjmNkwFCmTS7T8zC8c=\n-----END PRIVATE KEY-----\n','-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuEoY6jw0jziwnrL8LxCq\ncP0cXVa1TWvOsgLPpZp3HvNhCF79SHLqB+tzVelWU2AuI3EdPRWcboIygwciHxb4\nopFzHRBafiaPUeFM1TPE+5EVyGE1DUYlk/RqUbKZ28xj/hHwYFVSf04AOGgaFOYR\nvnQRvP7YYzRU9P4zRVZ+Rgs80rrlkeeKcAW83aVBxMMSVwGjSbQsX4KxXGnn7tjw\nQdW0iJKpsQDieKzrKaK1zCSudnS0ibOkI7HwCqWkUVBUjofp4JGd9TekJ2MT57Ic\nJ69WE9iTfU/eCNn/J0Nivr3VROFAQ/8FqDHRY0Zph455mYzzJfb6P0fYd++2iSKy\nNQIDAQAB\n-----END PUBLIC KEY-----\n');
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
('DoctrineMigrations\\Version20211221152434','2024-03-27 16:31:30',2662),
('DoctrineMigrations\\Version20220315154619','2024-03-27 16:31:33',71),
('DoctrineMigrations\\Version20220315160423','2024-03-27 16:31:33',72),
('DoctrineMigrations\\Version20220316163151','2024-03-27 16:31:33',58),
('DoctrineMigrations\\Version20220817140217','2024-03-27 16:31:33',521),
('DoctrineMigrations\\Version20220922122925','2024-03-27 16:31:34',118),
('DoctrineMigrations\\Version20221025152327','2024-03-27 16:31:34',317),
('DoctrineMigrations\\Version20221026082629','2024-03-27 16:31:34',1),
('DoctrineMigrations\\Version20221026092903','2024-03-27 16:31:34',58),
('DoctrineMigrations\\Version20221110134715','2024-03-27 16:31:34',240),
('DoctrineMigrations\\Version20221124105003','2024-03-27 16:31:35',226),
('DoctrineMigrations\\Version20221124140959','2024-03-27 16:31:35',183),
('DoctrineMigrations\\Version20221125153425','2024-03-27 16:31:35',63),
('DoctrineMigrations\\Version20221128110154','2024-03-27 16:31:35',55),
('DoctrineMigrations\\Version20221128153639','2024-03-27 16:31:35',56),
('DoctrineMigrations\\Version20221130081548','2024-03-27 16:31:35',54),
('DoctrineMigrations\\Version20221130093308','2024-03-27 16:31:35',54),
('DoctrineMigrations\\Version20221130101606','2024-03-27 16:31:35',56),
('DoctrineMigrations\\Version20230103105504','2024-03-27 16:31:35',58),
('DoctrineMigrations\\Version20230103130839','2024-03-27 16:31:35',59),
('DoctrineMigrations\\Version20230103131821','2024-03-27 16:31:35',68),
('DoctrineMigrations\\Version20230116083821UpdateImapEcnryptionTypes','2024-03-27 16:31:35',1),
('DoctrineMigrations\\Version20230118091904','2024-03-27 16:31:35',52),
('DoctrineMigrations\\Version20230118130123','2024-03-27 16:31:36',161),
('DoctrineMigrations\\Version20230301132150','2024-03-27 16:31:36',34),
('DoctrineMigrations\\Version20230302085930','2024-03-27 16:31:36',44),
('DoctrineMigrations\\Version20230630095941','2024-03-27 16:31:36',89),
('DoctrineMigrations\\Version20231117134044','2024-03-27 16:31:36',33),
('DoctrineMigrations\\Version20231120143030','2024-03-27 16:31:36',35),
('DoctrineMigrations\\Version20231207083955','2024-03-27 16:31:36',28),
('DoctrineMigrations\\Version20240223155415','2024-03-27 16:31:36',1123),
('DoctrineMigrations\\Version20240327095040','2024-03-27 16:31:37',365),
('DoctrineMigrations\\Version20240327151737','2024-03-27 16:31:37',291);
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `domain`
--

LOCK TABLES `domain` WRITE;
/*!40000 ALTER TABLE `domain` DISABLE KEYS */;
INSERT INTO `domain` VALUES
(1,2,'laissepasser.fr','smtp.test','smtp.test','2024-03-27 16:32:31',143,'',1,'smtp:[smtp.test]:25','<p><strong>V&eacute;rification de votre email</strong><br /> Merci de valider votre email en validant le captcha ci-dessous</p> ','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">Validation de votre adresse email</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">Bonjour,\n									<p>Vous avez tent&eacute; d&#39;envoyer un mail pour la premi&egrave;re fois &agrave; <strong>[EMAIL_DEST]</strong><br />\n									Cette messagerie est prot&eacute;g&eacute;e par l&#39;anti-spam AgentJ, nous vous demandons de v&eacute;rifier votre email en cliquant sur le lien suivant :</p>\n									</td>\n								</tr>\n								<tr>\n									<td align=\"center\" height=\"76\"><a href=\"[URL_CAPTCHA]\" style=\"           \n                           font-size:16px; ;                                                                                         \n                           -moz-border-radius: 5px;\n                           -webkit-border-radius: 5px;\n                           border-radius: 5px;\n                           line-height: 16px;\n                           background-color: #00a9d4;\n                           text-align: center;\n                           width: 190px;\n                           padding: 5px;\n                           color: #fff;\n                           text-decoration: none;\n                           \">Confirmer mon adresse</a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<p>Cette op&eacute;ration ne sera pas r&eacute;p&eacute;t&eacute;e lors des prochains envois.<br />\n									Merci pour votre compr&eacute;hension.<br />\n									Pour plus d&#39;informations sur la solution anti-spam AgentJ, <a href=\"https://agentj.io/\">cliquez ici</a></p>\n									</td>\n								</tr>\n								<tr>\n									<td>&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"text-align: center;height:100px;color: #3c4858;font-size: 12px;\"><img src=\"https://agentj.io/sites/agentj.io/themes/agentj/motif-agent-j.png\" style=\"width:100%\" /></td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"rnb-col-1\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">RAPPORT AGENTJ</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px; mso-hide: all;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px;  color:#3c4858;\">\n									<p>Bonjour <strong>[USERNAME]</strong>,</p>\n\n									<p>Ci dessous la liste des mails non trait&eacute;s, en attente sur AgentJ</p>\n\n									<p>[LIST_MAIL_MSGS]</p>\n									</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; font-family:Arial,Helvetica,sans-serif, sans-serif; color:#3c4858;\">&nbsp;\n									<p>Merci et bonne journ&eacute;e.</p>\n									</td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n',0.5,'<p>Merci et bonne journ&eacute;e</p> ',NULL,NULL,0,'fr',25,1),
(2,5,'blocnormal.fr','smtp.test','smtp.test','2024-03-27 16:32:54',143,'',1,'smtp:[smtp.test]:25','<p><strong>V&eacute;rification de votre email</strong><br /> Merci de valider votre email en validant le captcha ci-dessous</p> ','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">Validation de votre adresse email</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">Bonjour,\n									<p>Vous avez tent&eacute; d&#39;envoyer un mail pour la premi&egrave;re fois &agrave; <strong>[EMAIL_DEST]</strong><br />\n									Cette messagerie est prot&eacute;g&eacute;e par l&#39;anti-spam AgentJ, nous vous demandons de v&eacute;rifier votre email en cliquant sur le lien suivant :</p>\n									</td>\n								</tr>\n								<tr>\n									<td align=\"center\" height=\"76\"><a href=\"[URL_CAPTCHA]\" style=\"           \n                           font-size:16px; ;                                                                                         \n                           -moz-border-radius: 5px;\n                           -webkit-border-radius: 5px;\n                           border-radius: 5px;\n                           line-height: 16px;\n                           background-color: #00a9d4;\n                           text-align: center;\n                           width: 190px;\n                           padding: 5px;\n                           color: #fff;\n                           text-decoration: none;\n                           \">Confirmer mon adresse</a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<p>Cette op&eacute;ration ne sera pas r&eacute;p&eacute;t&eacute;e lors des prochains envois.<br />\n									Merci pour votre compr&eacute;hension.<br />\n									Pour plus d&#39;informations sur la solution anti-spam AgentJ, <a href=\"https://agentj.io/\">cliquez ici</a></p>\n									</td>\n								</tr>\n								<tr>\n									<td>&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"text-align: center;height:100px;color: #3c4858;font-size: 12px;\"><img src=\"https://agentj.io/sites/agentj.io/themes/agentj/motif-agent-j.png\" style=\"width:100%\" /></td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"rnb-col-1\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">RAPPORT AGENTJ</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px; mso-hide: all;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px;  color:#3c4858;\">\n									<p>Bonjour <strong>[USERNAME]</strong>,</p>\n\n									<p>Ci dessous la liste des mails non trait&eacute;s, en attente sur AgentJ</p>\n\n									<p>[LIST_MAIL_MSGS]</p>\n									</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; font-family:Arial,Helvetica,sans-serif, sans-serif; color:#3c4858;\">&nbsp;\n									<p>Merci et bonne journ&eacute;e.</p>\n									</td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n',0.5,'<p>Merci et bonne journ&eacute;e</p> ',NULL,'noreply@blocnormal.fr',0,'fr',25,2),
(3,5,'m365.probesys.com','smtp.probesys.com','imap.probesys.com','2024-07-23 09:52:48',993,'ssl',1,'smtp:[smtp.probesys.com]:25','<p><strong>V&eacute;rification de votre email</strong><br /> Merci de valider votre email en validant le captcha ci-dessous</p> ','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">Validation de votre adresse email</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">Bonjour,\n									<p>Vous avez tent&eacute; d&#39;envoyer un mail pour la premi&egrave;re fois &agrave; <strong>[EMAIL_DEST]</strong><br />\n									Cette messagerie est prot&eacute;g&eacute;e par l&#39;anti-spam AgentJ, nous vous demandons de v&eacute;rifier votre email en cliquant sur le lien suivant :</p>\n									</td>\n								</tr>\n								<tr>\n									<td align=\"center\" height=\"76\"><a href=\"[URL_CAPTCHA]\" style=\"           \n                           font-size:16px; ;                                                                                         \n                           -moz-border-radius: 5px;\n                           -webkit-border-radius: 5px;\n                           border-radius: 5px;\n                           line-height: 16px;\n                           background-color: #00a9d4;\n                           text-align: center;\n                           width: 190px;\n                           padding: 5px;\n                           color: #fff;\n                           text-decoration: none;\n                           \">Confirmer mon adresse</a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<p>Cette op&eacute;ration ne sera pas r&eacute;p&eacute;t&eacute;e lors des prochains envois.<br />\n									Merci pour votre compr&eacute;hension.<br />\n									Pour plus d&#39;informations sur la solution anti-spam AgentJ, <a href=\"https://agentj.io/\">cliquez ici</a></p>\n									</td>\n								</tr>\n								<tr>\n									<td>&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"text-align: center;height:100px;color: #3c4858;font-size: 12px;\"><img src=\"https://agentj.io/sites/agentj.io/themes/agentj/motif-agent-j.png\" style=\"width:100%\" /></td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"rnb-col-1\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">RAPPORT AGENTJ</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px; mso-hide: all;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px;  color:#3c4858;\">\n									<p>Bonjour <strong>[USERNAME]</strong>,</p>\n\n									<p>Ci dessous la liste des mails non trait&eacute;s, en attente sur AgentJ</p>\n\n									<p>[LIST_MAIL_MSGS]</p>\n									</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; font-family:Arial,Helvetica,sans-serif, sans-serif; color:#3c4858;\">&nbsp;\n									<p>Merci et bonne journ&eacute;e.</p>\n									</td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n',3,'<p>Merci et bonne journ&eacute;e</p> ',NULL,NULL,1,'fr',25,3),
(4,5,'probesys.com','smtp.probesys.com','imap.probesys.com','2024-07-23 09:54:07',993,'ssl',1,'smtp:[smtp.probesys.com]:25','<p><strong>V&eacute;rification de votre email</strong><br /> Merci de valider votre email en validant le captcha ci-dessous</p> ','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">Validation de votre adresse email</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">Bonjour,\n									<p>Vous avez tent&eacute; d&#39;envoyer un mail pour la premi&egrave;re fois &agrave; <strong>[EMAIL_DEST]</strong><br />\n									Cette messagerie est prot&eacute;g&eacute;e par l&#39;anti-spam AgentJ, nous vous demandons de v&eacute;rifier votre email en cliquant sur le lien suivant :</p>\n									</td>\n								</tr>\n								<tr>\n									<td align=\"center\" height=\"76\"><a href=\"[URL_CAPTCHA]\" style=\"           \n                           font-size:16px; ;                                                                                         \n                           -moz-border-radius: 5px;\n                           -webkit-border-radius: 5px;\n                           border-radius: 5px;\n                           line-height: 16px;\n                           background-color: #00a9d4;\n                           text-align: center;\n                           width: 190px;\n                           padding: 5px;\n                           color: #fff;\n                           text-decoration: none;\n                           \">Confirmer mon adresse</a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<p>Cette op&eacute;ration ne sera pas r&eacute;p&eacute;t&eacute;e lors des prochains envois.<br />\n									Merci pour votre compr&eacute;hension.<br />\n									Pour plus d&#39;informations sur la solution anti-spam AgentJ, <a href=\"https://agentj.io/\">cliquez ici</a></p>\n									</td>\n								</tr>\n								<tr>\n									<td>&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"text-align: center;height:100px;color: #3c4858;font-size: 12px;\"><img src=\"https://agentj.io/sites/agentj.io/themes/agentj/motif-agent-j.png\" style=\"width:100%\" /></td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"rnb-col-1\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">RAPPORT AGENTJ</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px; mso-hide: all;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px;  color:#3c4858;\">\n									<p>Bonjour <strong>[USERNAME]</strong>,</p>\n\n									<p>Ci dessous la liste des mails non trait&eacute;s, en attente sur AgentJ</p>\n\n									<p>[LIST_MAIL_MSGS]</p>\n									</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; font-family:Arial,Helvetica,sans-serif, sans-serif; color:#3c4858;\">&nbsp;\n									<p>Merci et bonne journ&eacute;e.</p>\n									</td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n',2.1,'<p>Merci et bonne journ&eacute;e</p> ',NULL,NULL,1,'fr',25,4);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
INSERT INTO `log` VALUES
(1,NULL,'Authentification request sent','t9aUf8kV30gB','<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">Validation de votre adresse email</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">Bonjour,\n									<p>Vous avez tent&eacute; d&#39;envoyer un mail pour la premi&egrave;re fois &agrave; <strong>user@blocnormal.fr</strong><br />\n									Cette messagerie est prot&eacute;g&eacute;e par l&#39;anti-spam AgentJ, nous vous demandons de v&eacute;rifier votre email en cliquant sur le lien suivant :</p>\n									</td>\n								</tr>\n								<tr>\n									<td align=\"center\" height=\"76\"><a href=\"https://agentj.example.com/check/MTcyMjMzMjQwMlRrd2J3eFN6bmxuZ2tYZ3gvWGF1eUV3c1VJdjNoZDhtZ3kvQVp2VGNDdjhnL1l1bE83TmlWeHBkd3JWYmF1Z04=\" style=\"           \n                           font-size:16px; ;                                                                                         \n                           -moz-border-radius: 5px;\n                           -webkit-border-radius: 5px;\n                           border-radius: 5px;\n                           line-height: 16px;\n                           background-color: #00a9d4;\n                           text-align: center;\n                           width: 190px;\n                           padding: 5px;\n                           color: #fff;\n                           text-decoration: none;\n                           \">Confirmer mon adresse</a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<p>Cette op&eacute;ration ne sera pas r&eacute;p&eacute;t&eacute;e lors des prochains envois.<br />\n									Merci pour votre compr&eacute;hension.<br />\n									Pour plus d&#39;informations sur la solution anti-spam AgentJ, <a href=\"https://agentj.io/\">cliquez ici</a></p>\n									</td>\n								</tr>\n								<tr>\n									<td>&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"text-align: center;height:100px;color: #3c4858;font-size: 12px;\"><img src=\"https://agentj.io/sites/agentj.io/themes/agentj/motif-agent-j.png\" style=\"width:100%\" /></td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n','2024-07-23 09:40:03',NULL,'2024-07-23 09:40:03');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maddr`
--

LOCK TABLES `maddr` WRITE;
/*!40000 ALTER TABLE `maddr` DISABLE KEYS */;
INSERT INTO `maddr` VALUES
(1,0,'root@smtp.test',NULL,'test.smtp'),
(2,0,'user@blocnormal.fr',NULL,'fr.blocnormal'),
(3,0,'user@laissepasser.fr',NULL,'fr.laissepasser');
/*!40000 ALTER TABLE `maddr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mail_count`
--

DROP TABLE IF EXISTS `mail_count`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mail_count` (
  `id` varchar(40) NOT NULL,
  `date` bigint(20) NOT NULL,
  `recipient_count` int(11) DEFAULT 1,
  `instance` varchar(40) NOT NULL,
  `protocol_state` varchar(10) NOT NULL,
  KEY `mail_count_index` (`id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mail_count`
--

LOCK TABLES `mail_count` WRITE;
/*!40000 ALTER TABLE `mail_count` DISABLE KEYS */;
INSERT INTO `mail_count` VALUES
('user@blocnormal.fr',1721727612,1,'91.669f7a7c.1d9b1.0','DATA'),
('user@laissepasser.fr',1721727616,1,'91.669f7a80.6ed50.0','DATA'),
('user@blocnormal.fr',1721727714,1,'91.669f7ae2.4b5e0.0','DATA'),
('user@laissepasser.fr',1721727804,1,'91.669f7b3c.8f492.0','DATA'),
('user@blocnormal.fr',1721727894,1,'91.669f7b96.cdae8.0','DATA'),
('user@laissepasser.fr',1721727898,1,'91.669f7b9a.87d0a.0','DATA'),
('user@laissepasser.fr',1721727898,1,'91.669f7b9a.c0575.0','DATA'),
('user@laissepasser.fr',1721727899,1,'91.669f7b9b.82a4.0','DATA'),
('user@laissepasser.fr',1721727899,1,'91.669f7b9b.35ff5.0','DATA'),
('user@laissepasser.fr',1721727909,1,'91.669f7ba5.7854f.0','DATA');
/*!40000 ALTER TABLE `mail_count` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mailaddr`
--

LOCK TABLES `mailaddr` WRITE;
/*!40000 ALTER TABLE `mailaddr` DISABLE KEYS */;
INSERT INTO `mailaddr` VALUES
(1,0,'@.'),
(2,20,'root@smtp.test');
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
INSERT INTO `msgrcpt` VALUES
(0,'EcAAeA5DAxSE',1,2,NULL,'N','V','D','','N','N',0,'250 2.7.0 Ok, discarded, id=00073-02 - INFECTED: Win.Test.EICAR_HDB-1',0,NULL),
(0,'P3YRcbqTGla0',1,3,NULL,'N','V','P','','N','Y',0,'250 2.0.0 from MTA(smtp:[smtp]:10025): 250 2.0.0 Ok: queued as 6E56E9AADA6',0,NULL),
(0,'t9aUf8kV30gB',1,2,NULL,'N','S','D','','N','N',-0.8,'250 2.7.0 Ok, discarded, id=00072-01 - spam',1721727603,NULL);
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
INSERT INTO `msgs` VALUES
(0,'EcAAeA5DAxSE',NULL,1,'DgczluNYj4b2','00073-02',1721727624,'20240723T094024Z','MYNET','192.168.144.14',1843,'N','V','Q','EcAAeA5DAxSE','N',0,'<20240723114022.000183@883101f0d27f>','root@smtp.test','test Tue, 23 Jul 2024 11:40:22 +0200','agentj.example.com',0,0,NULL,0),
(0,'P3YRcbqTGla0',NULL,1,'av8Zl1Q1eiZV','00074-02',1721727713,'20240723T094153Z','MYNET','192.168.144.14',1849,'N','V','Q','P3YRcbqTGla0','N',0,'<20240723114153.000190@883101f0d27f>','root@smtp.test','test Tue, 23 Jul 2024 11:41:53 +0200','agentj.example.com',0,0,NULL,0),
(0,'t9aUf8kV30gB',NULL,1,'SL8sU-JwWdWH','00072-01',1721727589,'20240723T093949Z','MYNET','192.168.144.14',1338,'N','S','Q','t9aUf8kV30gB','N',-0.8,'<20240723113949.000077@883101f0d27f>','root@smtp.test','test Tue, 23 Jul 2024 11:39:49 +0200','agentj.example.com',0,1721727603,NULL,0);
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
INSERT INTO `out_msgrcpt` VALUES
(0,'2cM5lFlY-BQ8',1,1,NULL,'N','V','D','','N','N',0,'250 2.7.0 Ok, discarded, id=00074-01 - INFECTED: Win.Test.EICAR_HDB-1',0,NULL),
(0,'5StXcyYIgh-I',1,1,NULL,'N','C','P','','N','N',1.185,'250 2.0.0 from MTA(smtp:[outsmtp]:10025): 250 2.0.0 Ok: queued as 1503C9AADA5',0,NULL),
(0,'T6-DBDgX7fi6',1,1,NULL,'N','C','P','','N','N',0,'250 2.0.0 from MTA(smtp:[outsmtp]:10025): 250 2.0.0 Ok: queued as E82919AADA6',0,NULL),
(0,'YVumIx0-ZNOl',1,1,NULL,'N','C','P','','N','N',-0.8,'250 2.0.0 from MTA(smtp:[outsmtp]:10025): 250 2.0.0 Ok: queued as 2EB8F9AADA6',0,NULL),
(0,'h2ykBXuiLt-i',1,1,NULL,'N','C','P','','N','N',1.985,'250 2.0.0 from MTA(smtp:[outsmtp]:10025): 250 2.0.0 Ok: queued as 820099AADA6',0,NULL),
(0,'lMS_roD6Qegu',1,1,NULL,'N','C','P','','N','N',1.185,'250 2.0.0 from MTA(smtp:[outsmtp]:10025): 250 2.0.0 Ok: queued as 894EC9AADA5',0,NULL),
(0,'mhD2dL2yzIBZ',1,1,NULL,'N','C','P','','N','N',-0.8,'250 2.0.0 from MTA(smtp:[outsmtp]:10025): 250 2.0.0 Ok: queued as D29829AADA6',0,NULL),
(0,'rMrcHGZ7seSn',1,1,NULL,'N','C','P','','N','N',1.185,'250 2.0.0 from MTA(smtp:[outsmtp]:10025): 250 2.0.0 Ok: queued as 423339AADA5',0,NULL),
(0,'tsklDbyT9013',1,1,NULL,'N','C','P','','N','N',1.185,'250 2.0.0 from MTA(smtp:[outsmtp]:10025): 250 2.0.0 Ok: queued as 508529AADA9',0,NULL),
(0,'w0rJV_rJQtdI',1,1,NULL,'N','V','D','','N','N',0,'250 2.7.0 Ok, discarded, id=00072-02 - INFECTED: Win.Test.EICAR_HDB-1',0,NULL);
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
INSERT INTO `out_msgs` VALUES
(0,'2cM5lFlY-BQ8',NULL,2,'Y4skt-sPgoYp','00074-01',1721727714,'20240723T094154Z','MYNET','192.168.144.14',1521,'N','V','Q','2cM5lFlY-BQ8','N',0,'<20240723114154.000199@883101f0d27f>','user@blocnormal.fr','test Tue, 23 Jul 2024 11:41:54 +0200','agentj.example.com',0,0,NULL,0),
(0,'5StXcyYIgh-I',NULL,3,'MtgYpJ8Pq6PK','00073-03',1721727900,'20240723T094500Z','MYNET','192.168.144.14',1026,'N','C','Q','5StXcyYIgh-I','N',1.185,'<20240723114458.000232@883101f0d27f>','user@laissepasser.fr','test Tue, 23 Jul 2024 11:44:58 +0200','agentj.example.com',0,0,NULL,0),
(0,'T6-DBDgX7fi6',NULL,2,'VY_i3qD4uX7U','00072-01',1721727612,'20240723T094012Z','MYNET','192.168.144.14',1016,'N','C','Q','T6-DBDgX7fi6','N',0,'<20240723114012.000135@883101f0d27f>','user@blocnormal.fr','test Tue, 23 Jul 2024 11:40:12 +0200','agentj.example.com',0,0,NULL,0),
(0,'YVumIx0-ZNOl',NULL,3,'sNbHY5DOEy5V','00072-04',1721727909,'20240723T094509Z','MYNET','192.168.144.14',1020,'N','C','Q','YVumIx0-ZNOl','N',-0.8,'<20240723114509.000239@883101f0d27f>','user@laissepasser.fr','test Tue, 23 Jul 2024 11:45:09 +0200','agentj.example.com',0,0,NULL,0),
(0,'h2ykBXuiLt-i',NULL,2,'aPOT3F9i2sOd','00073-02',1721727894,'20240723T094454Z','MYNET','192.168.144.14',1022,'N','C','Q','h2ykBXuiLt-i','N',1.985,'<20240723114454.000213@883101f0d27f>','user@blocnormal.fr','test Tue, 23 Jul 2024 11:44:54 +0200','agentj.example.com',0,0,NULL,0),
(0,'lMS_roD6Qegu',NULL,3,'XgNgk381BRE6','00072-03',1721727898,'20240723T094458Z','MYNET','192.168.144.14',1026,'N','C','Q','lMS_roD6Qegu','N',1.185,'<20240723114458.000231@883101f0d27f>','user@laissepasser.fr','test Tue, 23 Jul 2024 11:44:58 +0200','agentj.example.com',0,0,NULL,0),
(0,'mhD2dL2yzIBZ',NULL,3,'8SOV-QtcrjXd','00073-01',1721727616,'20240723T094016Z','MYNET','192.168.144.14',1020,'N','C','Q','mhD2dL2yzIBZ','N',-0.8,'<20240723114016.000150@883101f0d27f>','user@laissepasser.fr','test Tue, 23 Jul 2024 11:40:16 +0200','agentj.example.com',0,0,NULL,0),
(0,'rMrcHGZ7seSn',NULL,3,'vqgXR6Pcu03n','00074-03',1721727900,'20240723T094500Z','MYNET','192.168.144.14',1026,'N','C','Q','rMrcHGZ7seSn','N',1.185,'<20240723114459.000233@883101f0d27f>','user@laissepasser.fr','test Tue, 23 Jul 2024 11:44:59 +0200','agentj.example.com',0,0,NULL,0),
(0,'tsklDbyT9013',NULL,3,'cnNGMrkBMFNt','00074-02',1721727898,'20240723T094458Z','MYNET','192.168.144.14',1026,'N','C','Q','tsklDbyT9013','N',1.185,'<20240723114458.000230@883101f0d27f>','user@laissepasser.fr','test Tue, 23 Jul 2024 11:44:58 +0200','agentj.example.com',0,0,NULL,0),
(0,'w0rJV_rJQtdI',NULL,3,'rm_MqM5MAFJh','00072-02',1721727804,'20240723T094324Z','MYNET','192.168.144.14',1525,'N','V','Q','w0rJV_rJQtdI','N',0,'<20240723114324.000206@883101f0d27f>','user@laissepasser.fr','test Tue, 23 Jul 2024 11:43:24 +0200','agentj.example.com',0,0,NULL,0);
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
INSERT INTO `out_quarantine` VALUES
(0,'2cM5lFlY-BQ8',1,'X-Envelope-From: <user@blocnormal.fr>\nX-Envelope-To: <root@smtp.test>\nX-Envelope-To-Blocked: <root@smtp.test>\nX-Quarantine-ID: <2cM5lFlY-BQ8>\nX-Amavis-Alert: INFECTED, message contains virus: Win.Test.EICAR_HDB-1\nX-Spam-Flag: NO\nX-Spam-Score: 0\nX-Spam-Level:\nX-Spam-Status: No, score=x tag=x tag2=x kill=x tests=[] autolearn=unavailable\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=blocnormal.fr\nReceived: from agentj.example.com ([192.168.144.12])\n by localhost (agentj.example.com [192.168.144.9]) (amavis, port 10024)\n with LMTP id 2cM5lFlY-BQ8 for <root@smtp.test>;\n Tue, 23 Jul 2024 11:41:54 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=blocnormal.fr;\n	s=agentj; t=1721727714;\n	bh=VkVhNlT594UynpnCIeMMy8Y4GyxWPU95463hxQY1gBY=;\n	h=Date:To:From:Subject:From;\n	b=Gyd2mi9KwVzcovGhBZA8GsplQ0NycDecx4b6sxnBnLSUNIObJ3Khdu2h8z0V0jGLW\n	 GM9Ncx0LDMIWTAPid9lLgeZYwHjA0qgCW3AbU4krkSiJcJJ1MyuXYWBfogexPxLpiO\n	 SwZ1A3vP1fFB6oICU6l+pJ1S8q9GzLqkX9AgxHDxb4N0WHF020tHWnBHOgCvLO0zlt\n	 EQ28gQ9g2IgEoIzSUtC3VPL/9JApv36Lb30kAwxo/QuDnepjCcDjkKY7dRggbkpW4W\n	 LZFwxN0Eqb+R0jk+YtAJNsc3RMk200MLxzwA1wqIUavyNQRuLP/wAimHbUO8p1WuaR\n	 MBcP+HkilEZcg==\nReceived: from 883101f0d27f (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id 4BC709AADA5\n	for <root@smtp.test>; Tue, 23 Jul 2024 11:41:54 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:41:54 +0200\nTo: root@smtp.test\nFrom: user@blocnormal.fr\nSubject: test Tue, 23 Jul 2024 11:41:54 +0200\nMessage-Id: <20240723114154.000199@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\nMIME-Version: 1.0\nContent-Type: multipart/mixed; boundary=\"----=_MIME_BOUNDARY_000_199\"\n\n------=_MIME_BOUNDARY_000_199\nContent-Type: text/plain\n\nsent from agentj\n------=_MIME_BOUNDARY_000_199\nContent-Type: application/octet-stream; name=\"eicar.com.txt\"\nContent-Description: eicar.com.txt\nContent-Disposition: attachment; filename=\"eicar.com.txt\"\nContent-Transfer-Encoding: BASE64\n\nWDVPIVAlQEFQWzRcUFpYNTQoUF4pN0NDKTd9JEVJQ0FSLVNUQU5EQVJELUFOVElWSVJVUy1URVNU\nLUZJTEUhJEgrSCo=\n\n------=_MIME_BOUNDARY_000_199--\n\n\n'),
(0,'5StXcyYIgh-I',1,'X-Envelope-From: <user@laissepasser.fr>\nX-Envelope-To: <root@smtp.test>\nX-Envelope-To-Blocked:\nX-Quarantine-ID: <5StXcyYIgh-I>\nX-Spam-Flag: NO\nX-Spam-Score: 1.185\nX-Spam-Level: *\nX-Spam-Status: No, score=1.185 tag=x tag2=x kill=x tests=[ALL_TRUSTED=-1,\n DKIM_INVALID=0.1, DKIM_SIGNED=0.1, PYZOR_CHECK=1.985]\n autolearn=no autolearn_force=no\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=laissepasser.fr\nReceived: from agentj.example.com ([192.168.144.12])\n by localhost (agentj.example.com [192.168.144.9]) (amavis, port 10024)\n with LMTP id 5StXcyYIgh-I for <root@smtp.test>;\n Tue, 23 Jul 2024 11:45:00 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=laissepasser.fr;\n	s=agentj; t=1721727899;\n	bh=ecGWgWCJeWxJFeM0urOVWP+KOlqqvsQYKOpYUP8nk7I=;\n	h=Date:To:From:Subject:From;\n	b=HUFzmP0ZQ7WJUkT4/v8zA99eh38iMwlRnzzt/wJLNc+0UB3rA3u94YpsZIBNBZr0z\n	 qHo6DNJKlxZ0f1nCBRWNp/5DXaGO14SH4kTxlxXZLVzMr2ADDxuBojary+4vKB4aaj\n	 EJ2zefLZUenymOMlGCGNIzplwlSQYJVNkiqiZZbofzy/HiNJCdpTPSwlLM/kuGoGfO\n	 HnjJc13hQafw0Jm7+CnOafjxKQrxiF4Qio65YH+5fgRHe9H5dMiF3u+8RMRWbEHupM\n	 7ZCi2h2E2dLJN4LQVJzT35CK/+wE9Ts49FKBhZYrdoua42ZFogwlSZTp2dFWPiYzoF\n	 Icg04uvINz4AQ==\nReceived: from 883101f0d27f (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id 086EA9AADA7\n	for <root@smtp.test>; Tue, 23 Jul 2024 11:44:59 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:44:58 +0200\nTo: root@smtp.test\nFrom: user@laissepasser.fr\nSubject: test Tue, 23 Jul 2024 11:44:58 +0200\nMessage-Id: <20240723114458.000232@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\n\nThis is a test mailing\n\n\n'),
(0,'T6-DBDgX7fi6',1,'X-Envelope-From: <user@blocnormal.fr>\nX-Envelope-To: <root@smtp.test>\nX-Envelope-To-Blocked:\nX-Quarantine-ID: <T6-DBDgX7fi6>\nX-Spam-Flag: NO\nX-Spam-Score: 0\nX-Spam-Level:\nX-Spam-Status: No, score=0 tag=x tag2=x kill=x tests=[ALL_TRUSTED=-1,\n DKIM_ADSP_NXDOMAIN=0.8, DKIM_INVALID=0.1, DKIM_SIGNED=0.1]\n autolearn=no autolearn_force=no\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=blocnormal.fr\nReceived: from agentj.example.com ([192.168.144.12])\n by localhost (agentj.example.com [192.168.144.9]) (amavis, port 10024)\n with LMTP id T6-DBDgX7fi6 for <root@smtp.test>;\n Tue, 23 Jul 2024 11:40:12 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=blocnormal.fr;\n	s=agentj; t=1721727612;\n	bh=5uNld8QBfDLq7LyKwuWBNjsZwcNSDXEOrIsfmaQDF3c=;\n	h=Date:To:From:Subject:From;\n	b=Wj6lay3I3U6nxo/r+OnMVO3OzyEf25VG1Fsg/0GpCojaAYGLUxkgIB9Aeh/vffTF+\n	 zSzJXdWsP83SH4lp9QI5Wx/ehuuSlGkB72M7Y2JeOWl/qGE2Cc3bhVIowXRpknv+LS\n	 nGOsEc3Suwut+tdgFI9n40Hm8xq1DZDGdpVXLkE7owK+rLU4fMhIzc0Rj0OYGkoc5m\n	 Fdct86GECtHvsHh6XeWt4isrJIbRwIX7fDDUaHxCIv5B+zDwajnQ/XkjkPm5ipCvXU\n	 u3rAs+d/Ny76joIaC87JHRqiVjS2TKcpKLtCpl14fCpZO0gLznWH7MjpDZyUe86xW0\n	 R3Qg6okWInW5w==\nReceived: from 883101f0d27f (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id 288B79AADA5\n	for <root@smtp.test>; Tue, 23 Jul 2024 11:40:12 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:40:12 +0200\nTo: root@smtp.test\nFrom: user@blocnormal.fr\nSubject: test Tue, 23 Jul 2024 11:40:12 +0200\nMessage-Id: <20240723114012.000135@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\n\nsent from agentj\n\n\n'),
(0,'YVumIx0-ZNOl',1,'X-Envelope-From: <user@laissepasser.fr>\nX-Envelope-To: <root@smtp.test>\nX-Envelope-To-Blocked:\nX-Quarantine-ID: <YVumIx0-ZNOl>\nX-Spam-Flag: NO\nX-Spam-Score: -0.8\nX-Spam-Level:\nX-Spam-Status: No, score=-0.8 tag=x tag2=x kill=x tests=[ALL_TRUSTED=-1,\n DKIM_INVALID=0.1, DKIM_SIGNED=0.1] autolearn=no autolearn_force=no\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=laissepasser.fr\nReceived: from agentj.example.com ([192.168.144.12])\n by localhost (agentj.example.com [192.168.144.9]) (amavis, port 10024)\n with LMTP id YVumIx0-ZNOl for <root@smtp.test>;\n Tue, 23 Jul 2024 11:45:09 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=laissepasser.fr;\n	s=agentj; t=1721727909;\n	bh=5uNld8QBfDLq7LyKwuWBNjsZwcNSDXEOrIsfmaQDF3c=;\n	h=Date:To:From:Subject:From;\n	b=SEe4OaE33CvqsSbzsSj58yaFamQNRick13jhPHIO2936g53Vl/R1Ea2seBy9RBpQz\n	 KCI4pdslDq8Mxr7Uw4ouWBaDxVPLWz6RfZ5U4g/C0BZQ6V2THnRKoDuTxgnTt+ghCH\n	 CRmTCG20FMmLHg0TEdlWIwlf850JdgMGh2wnqhsjBgXMgiEHTHBF8EB0ZpHNUGF7Zf\n	 ThuHcrU1cYP41UHvtvNjwKFezEwJR165mGXp2Y/c4DCYJhPjAKPdIqWBcBwqwtAjJS\n	 YLT6/+KBXapfzGSYUzt8fIYpaoa0gJwkCU7X4ZcXrtURYfXmCP5jYL10b+24ThuhUF\n	 G2tmLkupgrSWw==\nReceived: from 883101f0d27f (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id 789809AADA5\n	for <root@smtp.test>; Tue, 23 Jul 2024 11:45:09 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:45:09 +0200\nTo: root@smtp.test\nFrom: user@laissepasser.fr\nSubject: test Tue, 23 Jul 2024 11:45:09 +0200\nMessage-Id: <20240723114509.000239@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\n\nsent from agentj\n\n\n'),
(0,'h2ykBXuiLt-i',1,'X-Envelope-From: <user@blocnormal.fr>\nX-Envelope-To: <root@smtp.test>\nX-Envelope-To-Blocked:\nX-Quarantine-ID: <h2ykBXuiLt-i>\nX-Spam-Flag: NO\nX-Spam-Score: 1.985\nX-Spam-Level: *\nX-Spam-Status: No, score=1.985 tag=x tag2=x kill=x tests=[ALL_TRUSTED=-1,\n DKIM_ADSP_NXDOMAIN=0.8, DKIM_INVALID=0.1, DKIM_SIGNED=0.1, PYZOR_CHECK=1.985]\n autolearn=no autolearn_force=no\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=blocnormal.fr\nReceived: from agentj.example.com ([192.168.144.12])\n by localhost (agentj.example.com [192.168.144.9]) (amavis, port 10024)\n with LMTP id h2ykBXuiLt-i for <root@smtp.test>;\n Tue, 23 Jul 2024 11:44:54 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=blocnormal.fr;\n	s=agentj; t=1721727894;\n	bh=ecGWgWCJeWxJFeM0urOVWP+KOlqqvsQYKOpYUP8nk7I=;\n	h=Date:To:From:Subject:From;\n	b=bxSTCNGbW8P7wvxNxCdhgUDQ1aBaCTa+dJn8HlReHmX4rShWyJnslTfbkrlEt6Mgs\n	 nRtEM6rRq02zhySnI3PQz2a8zQPP1UBupmapn+UIA/Xgf7OAoHz4S3gDBKuAWdMaf0\n	 r1F0qwrlUWR5U/Qv51pt4K6OZQ1yYu6Bm9xwOiuZkKc9526sD7KCWuTJfsBaglrSMj\n	 +ejcWIenp9QYlT80ZAlfvMsb/3IY/OI5ysyt0zlGQyq0EGfHEpbnX04/Y2DtW3qyvU\n	 YNh1Ei79ozX7CUGg+tj+5yUb4/00zUG0BAUhV5h9AqGScHaFvDuRZw/R3DbzCk8Dsr\n	 GqG0gT10LEi0Q==\nReceived: from 883101f0d27f (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id CE3129AADA5\n	for <root@smtp.test>; Tue, 23 Jul 2024 11:44:54 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:44:54 +0200\nTo: root@smtp.test\nFrom: user@blocnormal.fr\nSubject: test Tue, 23 Jul 2024 11:44:54 +0200\nMessage-Id: <20240723114454.000213@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\n\nThis is a test mailing\n\n\n'),
(0,'lMS_roD6Qegu',1,'X-Envelope-From: <user@laissepasser.fr>\nX-Envelope-To: <root@smtp.test>\nX-Envelope-To-Blocked:\nX-Quarantine-ID: <lMS_roD6Qegu>\nX-Spam-Flag: NO\nX-Spam-Score: 1.185\nX-Spam-Level: *\nX-Spam-Status: No, score=1.185 tag=x tag2=x kill=x tests=[ALL_TRUSTED=-1,\n DKIM_INVALID=0.1, DKIM_SIGNED=0.1, PYZOR_CHECK=1.985]\n autolearn=no autolearn_force=no\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=laissepasser.fr\nReceived: from agentj.example.com ([192.168.144.12])\n by localhost (agentj.example.com [192.168.144.9]) (amavis, port 10024)\n with LMTP id lMS_roD6Qegu for <root@smtp.test>;\n Tue, 23 Jul 2024 11:44:58 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=laissepasser.fr;\n	s=agentj; t=1721727898;\n	bh=ecGWgWCJeWxJFeM0urOVWP+KOlqqvsQYKOpYUP8nk7I=;\n	h=Date:To:From:Subject:From;\n	b=K2apZLKk21eUp0rMbecEm6dDGuV66Z6+6zWDDmIiwraBdIg/yd+Y4B3mG5RH9LZzO\n	 xygU3V4IivVUGPc58SBilrobOXaq094kiUrFslH0o9wBIW4pe9dnJAc9AuF8QMU2pn\n	 5O/hQzijEwcu0hBdQPlOy4FMJdzODYrBh39nUQ0F7jVoHMxyHF7wUy7tEZeoVHldLO\n	 crG4ozyIdZNPX8kGH0ldp1jyRY4D2ryBE/rV34TEgA5kF1yxgiq/Hwl6F5XIpYcE1T\n	 cBxwHTxAqexvaLHLzkwLQl0mTA7I3xTGX0arHEe9u3UIMQ8blGGCpmgM1N0OBwsFL3\n	 juoy4RAqh/GDA==\nReceived: from 883101f0d27f (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id C09369AADA6\n	for <root@smtp.test>; Tue, 23 Jul 2024 11:44:58 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:44:58 +0200\nTo: root@smtp.test\nFrom: user@laissepasser.fr\nSubject: test Tue, 23 Jul 2024 11:44:58 +0200\nMessage-Id: <20240723114458.000231@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\n\nThis is a test mailing\n\n\n'),
(0,'mhD2dL2yzIBZ',1,'X-Envelope-From: <user@laissepasser.fr>\nX-Envelope-To: <root@smtp.test>\nX-Envelope-To-Blocked:\nX-Quarantine-ID: <mhD2dL2yzIBZ>\nX-Spam-Flag: NO\nX-Spam-Score: -0.8\nX-Spam-Level:\nX-Spam-Status: No, score=-0.8 tag=x tag2=x kill=x tests=[ALL_TRUSTED=-1,\n DKIM_INVALID=0.1, DKIM_SIGNED=0.1] autolearn=no autolearn_force=no\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=laissepasser.fr\nReceived: from agentj.example.com ([192.168.144.12])\n by localhost (agentj.example.com [192.168.144.9]) (amavis, port 10024)\n with LMTP id mhD2dL2yzIBZ for <root@smtp.test>;\n Tue, 23 Jul 2024 11:40:16 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=laissepasser.fr;\n	s=agentj; t=1721727616;\n	bh=5uNld8QBfDLq7LyKwuWBNjsZwcNSDXEOrIsfmaQDF3c=;\n	h=Date:To:From:Subject:From;\n	b=WvXrd+Cp//kOOin6x7TMOg6gCqnpxH2JInsiQxAeT9Cq0mjiiCb8YJxgTTW5JuaW7\n	 0lgpNFuaGoXEr1mnsKRRZBJLwZSiWFjqPGhIPThDl/sKD1G73RVwCUpeo7zZzojopG\n	 qVy18bH72QHLog+s3xPpGaPKW5vpLICtPKRBajBecwSKcGp4tLyQPwxF2PB4OTk4Pb\n	 SkYe4px8VPcnHKuPfqs23dMTO+O1qN9SQqPopWdNcdR2/JsPwZTRy53fN82K+WQW8s\n	 +AKjv2QWN5w0WXbDTheoLD01xGX58f6tzUokS8qmW6WP6NkFM5SC7PpoqnQVQBnKOn\n	 kin4CW+HAe3aA==\nReceived: from 883101f0d27f (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id 6F1039AADA5\n	for <root@smtp.test>; Tue, 23 Jul 2024 11:40:16 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:40:16 +0200\nTo: root@smtp.test\nFrom: user@laissepasser.fr\nSubject: test Tue, 23 Jul 2024 11:40:16 +0200\nMessage-Id: <20240723114016.000150@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\n\nsent from agentj\n\n\n'),
(0,'rMrcHGZ7seSn',1,'X-Envelope-From: <user@laissepasser.fr>\nX-Envelope-To: <root@smtp.test>\nX-Envelope-To-Blocked:\nX-Quarantine-ID: <rMrcHGZ7seSn>\nX-Spam-Flag: NO\nX-Spam-Score: 1.185\nX-Spam-Level: *\nX-Spam-Status: No, score=1.185 tag=x tag2=x kill=x tests=[ALL_TRUSTED=-1,\n DKIM_INVALID=0.1, DKIM_SIGNED=0.1, PYZOR_CHECK=1.985]\n autolearn=no autolearn_force=no\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=laissepasser.fr\nReceived: from agentj.example.com ([192.168.144.12])\n by localhost (agentj.example.com [192.168.144.9]) (amavis, port 10024)\n with LMTP id rMrcHGZ7seSn for <root@smtp.test>;\n Tue, 23 Jul 2024 11:45:00 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=laissepasser.fr;\n	s=agentj; t=1721727899;\n	bh=ecGWgWCJeWxJFeM0urOVWP+KOlqqvsQYKOpYUP8nk7I=;\n	h=Date:To:From:Subject:From;\n	b=YueHPdTnszdVFEM/vK6xz4t+KmKVKvamGSsswCGmh69yZTI/zmJqpaGXbXciTIoQJ\n	 dWOFrxtFFYgwkQKLMDzXuwX/B2U9a6HkpPHbA7Af/RKC/C8tRi/CCbz6vWpDXPL7o9\n	 OnObudNw8tnPURLcZxlmfmxYPy9TEG94W65+38Ww8XZRg1Ugg05SZnACxVMrPZe51m\n	 5Nm5QpvIRPAt5rr3nbm3rFR0oQ6cygor2TEwRPWvkbq4XaffO+v42GJwj9uCexqQ6T\n	 OLO9jO/O/nwnVpHW7NMzUCZwiRRUUqsmdSXPPUn7m/k/lWz7rBc+wObuvSw9JaESsE\n	 tU0cU3Hxc8qQQ==\nReceived: from 883101f0d27f (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id 362C59AADA8\n	for <root@smtp.test>; Tue, 23 Jul 2024 11:44:59 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:44:59 +0200\nTo: root@smtp.test\nFrom: user@laissepasser.fr\nSubject: test Tue, 23 Jul 2024 11:44:59 +0200\nMessage-Id: <20240723114459.000233@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\n\nThis is a test mailing\n\n\n'),
(0,'tsklDbyT9013',1,'X-Envelope-From: <user@laissepasser.fr>\nX-Envelope-To: <root@smtp.test>\nX-Envelope-To-Blocked:\nX-Quarantine-ID: <tsklDbyT9013>\nX-Spam-Flag: NO\nX-Spam-Score: 1.185\nX-Spam-Level: *\nX-Spam-Status: No, score=1.185 tag=x tag2=x kill=x tests=[ALL_TRUSTED=-1,\n DKIM_INVALID=0.1, DKIM_SIGNED=0.1, PYZOR_CHECK=1.985]\n autolearn=no autolearn_force=no\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=laissepasser.fr\nReceived: from agentj.example.com ([192.168.144.12])\n by localhost (agentj.example.com [192.168.144.9]) (amavis, port 10024)\n with LMTP id tsklDbyT9013 for <root@smtp.test>;\n Tue, 23 Jul 2024 11:44:58 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=laissepasser.fr;\n	s=agentj; t=1721727898;\n	bh=ecGWgWCJeWxJFeM0urOVWP+KOlqqvsQYKOpYUP8nk7I=;\n	h=Date:To:From:Subject:From;\n	b=K2apZLKk21eUp0rMbecEm6dDGuV66Z6+6zWDDmIiwraBdIg/yd+Y4B3mG5RH9LZzO\n	 xygU3V4IivVUGPc58SBilrobOXaq094kiUrFslH0o9wBIW4pe9dnJAc9AuF8QMU2pn\n	 5O/hQzijEwcu0hBdQPlOy4FMJdzODYrBh39nUQ0F7jVoHMxyHF7wUy7tEZeoVHldLO\n	 crG4ozyIdZNPX8kGH0ldp1jyRY4D2ryBE/rV34TEgA5kF1yxgiq/Hwl6F5XIpYcE1T\n	 cBxwHTxAqexvaLHLzkwLQl0mTA7I3xTGX0arHEe9u3UIMQ8blGGCpmgM1N0OBwsFL3\n	 juoy4RAqh/GDA==\nReceived: from 883101f0d27f (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id 8807D9AADA5\n	for <root@smtp.test>; Tue, 23 Jul 2024 11:44:58 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:44:58 +0200\nTo: root@smtp.test\nFrom: user@laissepasser.fr\nSubject: test Tue, 23 Jul 2024 11:44:58 +0200\nMessage-Id: <20240723114458.000230@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\n\nThis is a test mailing\n\n\n'),
(0,'w0rJV_rJQtdI',1,'X-Envelope-From: <user@laissepasser.fr>\nX-Envelope-To: <root@smtp.test>\nX-Envelope-To-Blocked: <root@smtp.test>\nX-Quarantine-ID: <w0rJV_rJQtdI>\nX-Amavis-Alert: INFECTED, message contains virus: Win.Test.EICAR_HDB-1\nX-Spam-Flag: NO\nX-Spam-Score: 0\nX-Spam-Level:\nX-Spam-Status: No, score=x tag=x tag2=x kill=x tests=[] autolearn=unavailable\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=laissepasser.fr\nReceived: from agentj.example.com ([192.168.144.12])\n by localhost (agentj.example.com [192.168.144.9]) (amavis, port 10024)\n with LMTP id w0rJV_rJQtdI for <root@smtp.test>;\n Tue, 23 Jul 2024 11:43:24 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=laissepasser.fr;\n	s=agentj; t=1721727804;\n	bh=r+wXkwpx4YzRVnz5KHGKVNq0Y/zrUvXgwd8tt5Q2S5s=;\n	h=Date:To:From:Subject:From;\n	b=D5gcvVhLQ6t70oy7owhuiDuixwknPefQ8dRU4DA+p+ONtQN/U4RQkEwhLKKL3blct\n	 vfPfnZt4xp6Ook2BzA7EHQyvxoOlrQkCAPqf8zXhE5Y+IIbWzUcoDkyEK3jlXJgpAa\n	 KjKpooOQ48BBByu4ESEv8YzFgur+Nw0soAd4TgOtpWXq7NBjpBxiMXlaaRLSH1V4lI\n	 r5Npui/Wu/owQ5JddhQIGZkyX8BuKd65ltL7MTFzt029dH742L3SVyR+i+sG1J8X4v\n	 BCIZcU6Asld3zRBgspt5IdbYCC4aA3a6UeY7P9EnLa8P5syy7dpGjAUKXnBQgJ+dWh\n	 DTEniDiw4y9iA==\nReceived: from 883101f0d27f (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id 8FC0E9AADA5\n	for <root@smtp.test>; Tue, 23 Jul 2024 11:43:24 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:43:24 +0200\nTo: root@smtp.test\nFrom: user@laissepasser.fr\nSubject: test Tue, 23 Jul 2024 11:43:24 +0200\nMessage-Id: <20240723114324.000206@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\nMIME-Version: 1.0\nContent-Type: multipart/mixed; boundary=\"----=_MIME_BOUNDARY_000_206\"\n\n------=_MIME_BOUNDARY_000_206\nContent-Type: text/plain\n\nsent from agentj\n------=_MIME_BOUNDARY_000_206\nContent-Type: application/octet-stream; name=\"eicar.com.txt\"\nContent-Description: eicar.com.txt\nContent-Disposition: attachment; filename=\"eicar.com.txt\"\nContent-Transfer-Encoding: BASE64\n\nWDVPIVAlQEFQWzRcUFpYNTQoUF4pN0NDKTd9JEVJQ0FSLVNUQU5EQVJELUFOVElWSVJVUy1URVNU\nLUZJTEUhJEgrSCo=\n\n------=_MIME_BOUNDARY_000_206--\n\n\n');
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
INSERT INTO `quarantine` VALUES
(0,'EcAAeA5DAxSE',1,'X-Envelope-From: <root@smtp.test>\nX-Envelope-To: <user@blocnormal.fr>\nX-Envelope-To-Blocked: <user@blocnormal.fr>\nX-Quarantine-ID: <EcAAeA5DAxSE>\nX-Amavis-Alert: INFECTED, message contains virus: Win.Test.EICAR_HDB-1\nX-Spam-Flag: YES\nX-Spam-Score: 0\nX-Spam-Level:\nX-Spam-Status: Yes, score=x tag=-20 tag2=-15 kill=-11 tests=[]\n autolearn=unavailable\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=smtp.test\nReceived: from agentj.example.com ([192.168.144.13])\n by localhost (agentj.example.com [192.168.144.8]) (amavis, port 10024)\n with LMTP id EcAAeA5DAxSE for <user@blocnormal.fr>;\n Tue, 23 Jul 2024 11:40:24 +0200 (CEST)\nAuthentication-Results: agentj.example.com;\n	dkim=pass (2048-bit key) header.d=smtp.test header.i=@smtp.test header.a=rsa-sha256 header.s=agentj header.b=K170l6bJ;\n	dkim-atps=neutral\nReceived: from smtp.test (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id E3EA99AADA5\n	for <user@blocnormal.fr>; Tue, 23 Jul 2024 11:40:23 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=simple/simple; s=agentj; bh=dsA+8nxHZxmZR\n	hiKRq5vp8WZ11il87Q6FAUIFCgITHk=; h=subject:from:to:date; d=smtp.test;\n	b=K170l6bJuB1LA6OkzC6mqURjEQx2/wdd0OY+hzj2le+o/sPLDxf63rBwxiiULvk4HBNo\n	O3k8LeZaErswSTcIu8g2hboPWbkmH4TsTdcyDoLOc+7bAKdt8xxhIvQ2XkqIOpU1UlICJ9\n	A+Hk9n8h7UYD3oLURxHnzJlBFMkdopoEGa6jnVu0s7kWDe3SWZ/kqdJdOfKlnXYxZ3plQp\n	Zfp6iJM4FQ0G242hmqrngu0CTZM7f4b8MsD6oEy2wa9ytK7zDSHk1ATVsTRJnFwDeGUO+V\n	vB3mvrJA67yD7Fn+6jygDa+AzS1qttDNULm9LQ59TIZWCfrDEOUNk3+ahiYRUdzw==\nReceived: from 883101f0d27f (localhost [127.0.0.1])\n	by smtp.test (OpenSMTPD) with ESMTP id 256ff00f\n	for <user@blocnormal.fr>;\n	Tue, 23 Jul 2024 11:40:23 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:40:22 +0200\nTo: user@blocnormal.fr\nFrom: root@smtp.test\nSubject: test Tue, 23 Jul 2024 11:40:22 +0200\nMessage-Id: <20240723114022.000183@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\nMIME-Version: 1.0\nContent-Type: multipart/mixed; boundary=\"----=_MIME_BOUNDARY_000_183\"\n\n------=_MIME_BOUNDARY_000_183\nContent-Type: text/plain\n\nsent to agentj\n------=_MIME_BOUNDARY_000_183\nContent-Type: application/octet-stream; name=\"eicar.com.txt\"\nContent-Description: eicar.com.txt\nContent-Disposition: attachment; filename=\"eicar.com.txt\"\nContent-Transfer-Encoding: BASE64\n\nWDVPIVAlQEFQWzRcUFpYNTQoUF4pN0NDKTd9JEVJQ0FSLVNUQU5EQVJELUFOVElWSVJVUy1URVNU\nLUZJTEUhJEgrSCo=\n\n------=_MIME_BOUNDARY_000_183--\n\n\n'),
(0,'P3YRcbqTGla0',1,'X-Envelope-From: <root@smtp.test>\nX-Envelope-To: <user@laissepasser.fr>\nX-Envelope-To-Blocked:\nX-Quarantine-ID: <P3YRcbqTGla0>\nX-Amavis-Alert: INFECTED, message contains virus: Win.Test.EICAR_HDB-1\nX-Spam-Flag: NO\nX-Spam-Score: 0\nX-Spam-Level:\nX-Spam-Status: No, score=x tag=-20 tag2=-15 kill=-11 WHITELISTED tests=[]\n autolearn=unavailable\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=smtp.test\nReceived: from agentj.example.com ([192.168.144.13])\n by localhost (agentj.example.com [192.168.144.8]) (amavis, port 10024)\n with LMTP id P3YRcbqTGla0 for <user@laissepasser.fr>;\n Tue, 23 Jul 2024 11:41:53 +0200 (CEST)\nAuthentication-Results: agentj.example.com;\n	dkim=pass (2048-bit key) header.d=smtp.test header.i=@smtp.test header.a=rsa-sha256 header.s=agentj header.b=EATKciG2;\n	dkim-atps=neutral\nReceived: from smtp.test (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id 3098C9AADA5\n	for <user@laissepasser.fr>; Tue, 23 Jul 2024 11:41:53 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=simple/simple; s=agentj; bh=ojbjZwFzTPc7i\n	XntNUmPT1fJsSXLdTEcLwYpa2b6Fo0=; h=subject:from:to:date; d=smtp.test;\n	b=EATKciG2nfxgJZwk/47L9Mk9nQnyiKhm5wpp785aKVKbeHKrdrlFcuBz10Edazwiuu6V\n	ZMOACdeIpS8BuRDxZj03Gi16q8RutJhuJjMDtnANcU90yLFX1nZgdHuGMyf7eq9KiNtpTV\n	HuLknLUuKRYaemHh6lhlRaBKjlexwKZ22BzatXB1hCaWl70GIp8cxBG5FHWM+GXKCJI4Ea\n	9ymaycqG9uTjPiiUw27kGIZ4GX3xbv2RFWh/Pd8n9eCaOVJTCK7/dycwfVNEuF6taY2IwK\n	O7yrK7dV0buFbZcC3mF1ieNLs30Ztrrc4IX9LzljIQCCIXuDlUa1fKquY/ahMTpA==\nReceived: from 883101f0d27f (localhost [127.0.0.1])\n	by smtp.test (OpenSMTPD) with ESMTP id 03fd4efd\n	for <user@laissepasser.fr>;\n	Tue, 23 Jul 2024 11:41:53 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:41:53 +0200\nTo: user@laissepasser.fr\nFrom: root@smtp.test\nSubject: test Tue, 23 Jul 2024 11:41:53 +0200\nMessage-Id: <20240723114153.000190@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\nMIME-Version: 1.0\nContent-Type: multipart/mixed; boundary=\"----=_MIME_BOUNDARY_000_190\"\n\n------=_MIME_BOUNDARY_000_190\nContent-Type: text/plain\n\nsent to agentj\n------=_MIME_BOUNDARY_000_190\nContent-Type: application/octet-stream; name=\"eicar.com.txt\"\nContent-Description: eicar.com.txt\nContent-Disposition: attachment; filename=\"eicar.com.txt\"\nContent-Transfer-Encoding: BASE64\n\nWDVPIVAlQEFQWzRcUFpYNTQoUF4pN0NDKTd9JEVJQ0FSLVNUQU5EQVJELUFOVElWSVJVUy1URVNU\nLUZJTEUhJEgrSCo=\n\n------=_MIME_BOUNDARY_000_190--\n\n\n'),
(0,'t9aUf8kV30gB',1,'X-Envelope-From: <root@smtp.test>\nX-Envelope-To: <user@blocnormal.fr>\nX-Envelope-To-Blocked: <user@blocnormal.fr>\nX-Quarantine-ID: <t9aUf8kV30gB>\nX-Spam-Flag: YES\nX-Spam-Score: -0.8\nX-Spam-Level:\nX-Spam-Status: Yes, score=-0.8 tag=-20 tag2=-15 kill=-11\n tests=[ALL_TRUSTED=-1, DKIM_INVALID=0.1, DKIM_SIGNED=0.1]\n autolearn=no autolearn_force=no\nAuthentication-Results: agentj.example.com (amavis); dkim=neutral\n reason=\"invalid (public key: not available)\" header.d=smtp.test\nReceived: from agentj.example.com ([192.168.144.13])\n by localhost (agentj.example.com [192.168.144.8]) (amavis, port 10024)\n with LMTP id t9aUf8kV30gB for <user@blocnormal.fr>;\n Tue, 23 Jul 2024 11:39:49 +0200 (CEST)\nAuthentication-Results: agentj.example.com;\n	dkim=pass (2048-bit key) header.d=smtp.test header.i=@smtp.test header.a=rsa-sha256 header.s=agentj header.b=AZ3+mm1Z;\n	dkim-atps=neutral\nReceived: from smtp.test (agentj-smtptest-1.agentj_default [192.168.144.14])\n	by agentj.example.com (Postfix) with ESMTP id 8576F9AADA5\n	for <user@blocnormal.fr>; Tue, 23 Jul 2024 11:39:49 +0200 (CEST)\nDKIM-Signature: v=1; a=rsa-sha256; c=simple/simple; s=agentj; bh=5EmiIA9n0BQBT\n	AF422szQtIzu81bm3x3KpJ5OFuligQ=; h=subject:from:to:date; d=smtp.test;\n	b=AZ3+mm1ZItCR+Sm+bASRgHbxKeq9jrFv9wKUxwVIngyGLsRpDyf4xfhB11O/pGQjNn0K\n	FlDX6X2s5IX+pu0OfuuC9Gda3oC3NJpfbb96bvsUZnWWq3WImbpmR0PiO/9z3kAwfywKK3\n	XguE18oUCV6+x11PYIAgKIRAKRoWp6o87kbJLdH3i4qkjZxnPk/Qk5j+rXgC0nE8e0kqa1\n	1EjUzsFqrU/3PuqIdw83VkcrGAeOwFEQ4dhNloPOsQkW7KcAawjlL87RUA/qRwKL5yNpkk\n	MuxlX5736nHd/aiLDQJHx/KmD+nTKPCnHnXh65wqUJ/6uCUN865LlzZMviL2wCYg==\nReceived: from 883101f0d27f (localhost [127.0.0.1])\n	by smtp.test (OpenSMTPD) with ESMTP id 59b94e56\n	for <user@blocnormal.fr>;\n	Tue, 23 Jul 2024 11:39:49 +0200 (CEST)\nDate: Tue, 23 Jul 2024 11:39:49 +0200\nTo: user@blocnormal.fr\nFrom: root@smtp.test\nSubject: test Tue, 23 Jul 2024 11:39:49 +0200\nMessage-Id: <20240723113949.000077@883101f0d27f>\nX-Mailer: swaks v20201014.0 jetmore.org/john/code/swaks/\n\nsent to agentj\n\n\n');
/*!40000 ALTER TABLE `quarantine` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rate_limits`
--

DROP TABLE IF EXISTS `rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `limits` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_limits`
--

LOCK TABLES `rate_limits` WRITE;
/*!40000 ALTER TABLE `rate_limits` DISABLE KEYS */;
INSERT INTO `rate_limits` VALUES
(1,'[[1,1],[100,8600]]');
/*!40000 ALTER TABLE `rate_limits` ENABLE KEYS */;
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
  `sender_rate_limit_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E9E7927C74` (`email`),
  UNIQUE KEY `UNIQ_1483A5E9211063FF` (`sender_rate_limit_id`),
  KEY `IDX_1483A5E92D29E3C6` (`policy_id`),
  KEY `IDX_1483A5E9115F0EE5` (`domain_id`),
  KEY `IDX_1483A5E921EE7D62` (`original_user_id`),
  KEY `IDX_1483A5E98361673C` (`origin_connector_id`),
  KEY `IDX_1483A5E957DAB4D1` (`out_policy_id`),
  CONSTRAINT `FK_1483A5E9115F0EE5` FOREIGN KEY (`domain_id`) REFERENCES `domain` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1483A5E9211063FF` FOREIGN KEY (`sender_rate_limit_id`) REFERENCES `rate_limits` (`id`),
  CONSTRAINT `FK_1483A5E921EE7D62` FOREIGN KEY (`original_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1483A5E92D29E3C6` FOREIGN KEY (`policy_id`) REFERENCES `policy` (`id`),
  CONSTRAINT `FK_1483A5E957DAB4D1` FOREIGN KEY (`out_policy_id`) REFERENCES `policy` (`id`),
  CONSTRAINT `FK_1483A5E98361673C` FOREIGN KEY (`origin_connector_id`) REFERENCES `connector` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,NULL,NULL,NULL,7,NULL,NULL,'admin',NULL,'$2y$13$fXwH1SEOfWTmPk92REnPq.Pbw6QVdBYNf0JCfuWbwB6pvW.CA4gJi','[\"ROLE_SUPER_ADMIN\"]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(2,2,1,NULL,2,'@laissepasser.fr','Domaine laissepasser.fr',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(3,5,2,NULL,2,'@blocnormal.fr','Domaine blocnormal.fr',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(4,5,2,NULL,7,'user@blocnormal.fr',NULL,'user@blocnormal.fr',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),
(5,2,1,NULL,7,'user@laissepasser.fr',NULL,'user@laissepasser.fr',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(6,5,3,NULL,2,'@m365.probesys.com','Domaine m365.probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(7,5,3,NULL,6,'probesys@m365.probesys.com','Société PROBESYS','probesys@m365.probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,1,NULL,NULL,NULL,'06e25b83-abaf-446a-91d0-821449564c49',1,NULL,'user@m365.probesys.com',NULL,NULL),
(8,5,3,7,6,'user@m365.probesys.com','Société PROBESYS','user@m365.probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,1,NULL,NULL,NULL,NULL,1,NULL,'user@m365.probesys.com',NULL,NULL),
(9,5,3,7,6,'christian.tresvaux@m365.probesys.com','Société PROBESYS','christian.tresvaux@m365.probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,1,NULL,NULL,NULL,NULL,1,NULL,'user@m365.probesys.com',NULL,NULL),
(10,5,3,NULL,6,'user4@m365.probesys.com','Karl Marx','user4@m365.probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,1,NULL,NULL,NULL,'1599bfed-354c-44ff-9a9c-33e224fe11a5',1,NULL,'user4@m365.probesys.com',NULL,NULL),
(11,5,3,NULL,6,'user3@m365.probesys.com','Christian Tresvaux','user3@m365.probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,1,NULL,NULL,NULL,'b9d136dc-f0c2-4a45-b359-734c1da3f455',1,NULL,'user3@m365.probesys.com',NULL,NULL),
(12,5,3,NULL,6,'user2@m365.probesys.com','User2','user2@m365.probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,1,NULL,NULL,NULL,'e13776d7-cb75-4781-a4b8-bf9bb1070617',1,NULL,'user2@m365.probesys.com',NULL,NULL),
(13,5,4,NULL,2,'@probesys.com','Domaine probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(14,5,4,NULL,6,'sebastien.poher@probesys.com','POHER','sebastien.poher@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'sebastien.poher',2,'uid=sebastien.poher,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(15,NULL,4,14,7,'sp@probesys.com',NULL,'sp@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(16,5,4,NULL,6,'fernando@probesys.com','LAGRANGE','fernando@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'fernando.lagrange',2,'uid=fernando.lagrange,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(17,NULL,4,16,7,'fl@probesys.com',NULL,'fl@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(18,NULL,4,16,7,'jfl@probesys.com',NULL,'jfl@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(19,NULL,4,16,7,'joseph@probesys.com',NULL,'joseph@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(20,NULL,4,16,7,'joseph.lagrange@probesys.com',NULL,'joseph.lagrange@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(21,NULL,4,16,7,'minitel@probesys.com',NULL,'minitel@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(22,NULL,4,16,7,'fernando.lagrange@probesys.com',NULL,'fernando.lagrange@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(23,5,4,NULL,6,'stephane.parunakian@probesys.com','PARUNAKIAN','stephane.parunakian@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'stephane.parunakian',2,'uid=stephane.parunakian,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(24,5,4,NULL,6,'yann.picot@probesys.com','PICOT','yann.picot@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'yann.picot',2,'uid=yann.picot,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(25,5,4,NULL,6,'philippe.godot@probesys.com','GODOT','philippe.godot@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'philippe.godot',2,'uid=philippe.godot,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(26,5,4,NULL,6,'vanessa.fayard@probesys.com','FAYARD','vanessa.fayard@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'vanessa.fayard',2,'uid=vanessa.fayard,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(27,5,4,NULL,6,'christian.tresvaux@probesys.com','TRESVAUX','christian.tresvaux@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'christian.tresvaux',2,'uid=christian.tresvaux,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(28,NULL,4,27,7,'ct@probesys.com',NULL,'ct@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(29,5,4,NULL,6,'samuel.laffon@probesys.com','LAFFON','samuel.laffon@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'samuel.laffon',2,'uid=samuel.laffon,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(30,5,4,NULL,6,'cyril.zorman@probesys.com','ZORMAN','cyril.zorman@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'cyril.zorman',2,'uid=cyril.zorman,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(31,5,4,NULL,6,'charline.lombardo@probesys.com','LOMBARDO','charline.lombardo@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'charline.lombardo',2,'uid=charline.lombardo,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(32,NULL,4,31,7,'charline@probesys.com',NULL,'charline@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(33,5,4,NULL,6,'gitlab@probesys.com','LAB','gitlab@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'gitlab',2,'uid=gitlab,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(34,5,4,NULL,6,'grosadminbleu@probesys.com','BIGBLUEBUTTON','grosadminbleu@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'grosadminbleu',2,'uid=grosadminbleu,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(35,5,4,NULL,6,'grosboutonbleu@probesys.com','BIGBLUEBUTTON','grosboutonbleu@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'grosboutonbleu',2,'uid=grosboutonbleu,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(36,5,4,NULL,6,'mattermail@probesys.com','MATTERMOST','mattermail@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'mattermail',2,'uid=mattermail,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(37,5,4,NULL,6,'backups@probesys.com','BACKUPS','backups@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'backups',2,'uid=backups,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(38,NULL,4,37,7,'sauvegarde@probesys.com',NULL,'sauvegarde@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(39,NULL,4,37,7,'sauvegardes@probesys.com',NULL,'sauvegardes@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(40,NULL,4,37,7,'backup@probesys.com',NULL,'backup@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(41,5,4,NULL,6,'cnil@probesys.com','TROIZAIRE','cnil@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'cnil',2,'uid=cnil,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(42,5,4,NULL,6,'contact@probesys.com','PROBESYS','contact@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'contact',2,'uid=contact,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(43,NULL,4,42,7,'abuse@probesys.com',NULL,'abuse@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(44,NULL,4,42,7,'achat@probesys.com',NULL,'achat@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(45,NULL,4,42,7,'acheteur@probesys.com',NULL,'acheteur@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(46,NULL,4,42,7,'adminteam@probesys.com',NULL,'adminteam@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(47,NULL,4,42,7,'appel_offre@probesys.com',NULL,'appel_offre@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(48,NULL,4,42,7,'boutique@probesys.com',NULL,'boutique@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(49,NULL,4,42,7,'cluster@probesys.com',NULL,'cluster@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(50,NULL,4,42,7,'commande@probesys.com',NULL,'commande@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(51,NULL,4,42,7,'compta@probesys.com',NULL,'compta@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(52,NULL,4,42,7,'contact_ao@probesys.com',NULL,'contact_ao@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(53,NULL,4,42,7,'contacts@probesys.com',NULL,'contacts@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(54,NULL,4,42,7,'domaine@probesys.com',NULL,'domaine@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(55,NULL,4,42,7,'drupal@probesys.com',NULL,'drupal@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(56,NULL,4,42,7,'facebook@probesys.com',NULL,'facebook@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(57,NULL,4,42,7,'facture@probesys.com',NULL,'facture@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(58,NULL,4,42,7,'factures@probesys.com',NULL,'factures@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(59,NULL,4,42,7,'fax@probesys.com',NULL,'fax@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(60,NULL,4,42,7,'fontaine38@probesys.com',NULL,'fontaine38@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(61,NULL,4,42,7,'formaneo@probesys.com',NULL,'formaneo@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(62,NULL,4,42,7,'fotolia@probesys.com',NULL,'fotolia@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(63,NULL,4,42,7,'inscription@probesys.com',NULL,'inscription@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(64,NULL,4,42,7,'logcheck@probesys.com',NULL,'logcheck@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(65,NULL,4,42,7,'logmachines@probesys.com',NULL,'logmachines@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(66,NULL,4,42,7,'messagerie@probesys.com',NULL,'messagerie@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(67,NULL,4,42,7,'midis-scop@probesys.com',NULL,'midis-scop@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(68,NULL,4,42,7,'nagios.alert@probesys.com',NULL,'nagios.alert@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(69,NULL,4,42,7,'nagios@probesys.com',NULL,'nagios@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(70,NULL,4,42,7,'noc@probesys.com',NULL,'noc@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(71,NULL,4,42,7,'probeye@probesys.com',NULL,'probeye@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(72,NULL,4,42,7,'recrutement@probesys.com',NULL,'recrutement@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(73,NULL,4,42,7,'reponse_AO@probesys.com',NULL,'reponse_AO@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(74,NULL,4,42,7,'technique@probesys.com',NULL,'technique@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(75,NULL,4,42,7,'telephonie@probesys.com',NULL,'telephonie@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(76,NULL,4,42,7,'vendeur@probesys.com',NULL,'vendeur@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(77,NULL,4,42,7,'webmaster@probesys.com',NULL,'webmaster@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(78,NULL,4,42,7,'relais@probesys.com',NULL,'relais@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(79,NULL,4,42,7,'root@probesys.com',NULL,'root@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(80,NULL,4,42,7,'spam.police@probesys.com',NULL,'spam.police@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(81,NULL,4,42,7,'virus@probesys.com',NULL,'virus@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(82,NULL,4,42,7,'spam@probesys.com',NULL,'spam@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(83,NULL,4,42,7,'virusalert@probesys.com',NULL,'virusalert@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(84,5,4,NULL,6,'drs@probesys.com','TROIZAIRE','drs@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'drs',2,'uid=drs,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(85,5,4,NULL,6,'dagf@probesys.com','PROBESYS','dagf@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'dagf-probesys',2,'uid=dagf-probesys,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(86,5,4,NULL,6,'dev.web@probesys.com','PROBESYS','dev.web@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'dev.web',2,'uid=dev.web,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(87,5,4,NULL,6,'formation@probesys.com','TROIZAIRE','formation@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'formation',2,'uid=formation,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(88,5,4,NULL,6,'postmaster@probesys.com','PROBESYS','postmaster@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'postmaster',2,'uid=postmaster,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(89,NULL,4,88,7,'postmaster@test.probesys.net',NULL,'postmaster@test.probesys.net',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(90,5,4,NULL,6,'log@probesys.com','TROIZAIRE','log@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'log',2,'uid=log,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(91,5,4,NULL,6,'noreply@probesys.com','PROBESYS','noreply@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'noreply',2,'uid=noreply,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(92,5,4,NULL,6,'sav@probesys.com','TROIZAIRE','sav@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'sav',2,'uid=sav,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(93,5,4,NULL,6,'support@probesys.com','PROBESYS','support@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'support',2,'uid=support,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(94,NULL,4,93,7,'glpi-support@probesys.com',NULL,'glpi-support@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(95,NULL,4,93,7,'support.fontaine@probesys.com',NULL,'support.fontaine@probesys.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(96,5,4,NULL,6,'troizaire@probesys.com','TROIZAIRE','troizaire@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'troizaire',2,'uid=troizaire,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(97,5,4,NULL,6,'zorg@probesys.com','ZORMAN-MAILINGLISTS','zorg@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'zorg',2,'uid=zorg,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(98,5,4,NULL,6,'stephan.klein@probesys.com','KLEIN','stephan.klein@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'stephan.klein',2,'uid=stephan.klein,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(99,5,4,NULL,6,'marien.fressinaud@probesys.com','Fressinaud','marien.fressinaud@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'marien.fressinaud',2,'uid=marien.fressinaud,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(100,5,4,NULL,6,'elina.bufferne@probesys.com','BUFFERNE','elina.bufferne@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'elina.bufferne',2,'uid=elina.bufferne,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(101,5,4,NULL,6,'eudes.rault-teyssonneyre@probesys.com','RAULT-TEYSSONNEYRE','eudes.rault-teyssonneyre@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'eudes.rault-teyssonneyre',2,'uid=eudes.rault-teyssonneyre,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(102,5,4,NULL,6,'bookstack.admin@probesys.com','Bookstack','bookstack.admin@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'bookstack.admin',2,'uid=bookstack.admin,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(103,5,4,NULL,6,'samuel.assani@probesys.com','ASSANI','samuel.assani@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'samuel.assani',2,'uid=samuel.assani,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(104,5,4,NULL,6,'demo@probesys.com','demo','demo@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'demo',2,'uid=demo,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(105,5,4,NULL,6,'nathanael.desnoyers@probesys.com','Desnoyers','nathanael.desnoyers@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'nathanael.desnoyers',2,'uid=nathanael.desnoyers,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(106,5,4,NULL,6,'compte.detest@probesys.com','De Test','compte.detest@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'compte.detest',2,'uid=compte.detest,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(107,5,4,NULL,6,'thomas.garcin@probesys.com','GARCIN','thomas.garcin@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'thomas.garcin',2,'uid=thomas.garcin,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(108,5,4,NULL,6,'lois.poujade@probesys.com','POUJADE','lois.poujade@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'lois.poujade',2,'uid=lois.poujade,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(109,5,4,NULL,6,'jean.test@probesys.com','TEST','jean.test@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'jean.test',2,'uid=jean.test,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(110,5,4,NULL,6,'thomas.bouchez@probesys.com','BOUCHEZ','thomas.bouchez@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'thomas.bouchez',2,'uid=thomas.bouchez,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(111,5,4,NULL,6,'chloe.lagabrielle@probesys.com','LAGABRIELLE','chloe.lagabrielle@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'chloe.lagabrielle',2,'uid=chloe.lagabrielle,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL),
(112,5,4,NULL,6,'test.temp@probesys.com','FaitDesTests','test.temp@probesys.com',NULL,NULL,'[\"ROLE_USER\"]',NULL,NULL,NULL,NULL,NULL,NULL,'test.temp',2,'uid=test.temp,cn=users,cn=accounts,dc=idm,dc=probesys,dc=net',NULL,NULL,NULL);
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
(2,1,NULL,'W','2024-03-27 16:32:31',NULL,0),
(3,1,NULL,'0','2024-03-27 16:32:54',NULL,0),
(4,2,NULL,'W','2024-07-23 11:40:15',4,100),
(5,2,NULL,'W','2024-07-23 11:40:18',4,100),
(6,1,NULL,'0','2024-07-23 09:52:48',NULL,0),
(13,1,NULL,'0','2024-07-23 09:54:07',NULL,0);
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

-- Dump completed on 2024-07-23 11:57:29
