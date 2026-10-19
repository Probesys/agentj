<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Configure report schedules and track pending deliveries, keeping daily reports at 07:00 by default';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE domain
            ADD report_times JSON DEFAULT '["07:00"]' NOT NULL,
            ADD report_skip_weekends TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE users
            ADD report_times JSON DEFAULT NULL,
            ADD report_skip_weekends TINYINT(1) DEFAULT NULL,
            ADD report_period_end INT DEFAULT NULL,
            ADD report_next_at INT DEFAULT NULL,
            ADD INDEX idx_users_report_next_at (report_next_at)
        SQL);
        $next = new \DateTimeImmutable('today 07:00');
        if ($next < new \DateTimeImmutable()) {
            $next = $next->modify('+1 day');
        }

        $this->addSql('UPDATE users SET report_period_end = date_last_report');
        $this->addSql(<<<SQL
            UPDATE users u INNER JOIN domain d ON d.id = u.domain_id
            SET u.report_next_at = :next
            WHERE u.report = 1 AND d.active = 1 AND u.roles = :roles AND u.original_user_id IS NULL
        SQL, ['next' => $next->getTimestamp(), 'roles' => '["ROLE_USER"]']);
        $this->addSql(<<<SQL
            CREATE TABLE report_delivery (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                email VARCHAR(255) NOT NULL,
                scheduled_at INT NOT NULL,
                forced TINYINT(1) NOT NULL,
                period_end INT DEFAULT NULL,
                content JSON DEFAULT NULL,
                INDEX IDX_REPORT_DELIVERY_USER (user_id),
                UNIQUE INDEX uniq_report_delivery_user (user_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_REPORT_DELIVERY_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE report_delivery');
        $this->addSql('ALTER TABLE domain DROP report_times, DROP report_skip_weekends');
        $this->addSql(<<<SQL
            ALTER TABLE users DROP INDEX idx_users_report_next_at,
            DROP report_times, DROP report_skip_weekends, DROP report_period_end, DROP report_next_at
        SQL);
    }
}
