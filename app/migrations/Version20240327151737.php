<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240327151737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sender rate limit, tables used by policyd-rate-limit';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE if not exists `mail_count` (`id` varchar(40) NOT NULL, `date` bigint(20) NOT NULL, `recipient_count` int(11) DEFAULT 1, `instance` varchar(40) NOT NULL, `protocol_state` varchar(10) NOT NULL, KEY `mail_count_index` (`id`,`date`)) ENGINE=InnoDB');
        $this->addSql('CREATE TABLE rate_limits (id INT AUTO_INCREMENT NOT NULL, limits VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE users ADD sender_rate_limit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9211063FF FOREIGN KEY (sender_rate_limit_id) REFERENCES rate_limits (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9211063FF ON users (sender_rate_limit_id)');
    }

    public function down(Schema $schema): void
    {
	// mail_count is ignored by doctrine
        $this->addSql('drop table `mail_count`');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9211063FF');
        $this->addSql('DROP TABLE rate_limits');
        $this->addSql('DROP INDEX UNIQ_1483A5E9211063FF ON users');
        $this->addSql('ALTER TABLE users DROP sender_rate_limit_id');
    }
}
