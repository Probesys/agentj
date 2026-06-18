<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618082334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove daily stats';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE daily_stat DROP FOREIGN KEY `FK_64BEE0B4115F0EE5`');
        $this->addSql('DROP TABLE daily_stat');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE daily_stat (
                id INT AUTO_INCREMENT NOT NULL,
                date DATE NOT NULL,
                nb_untreated INT DEFAULT NULL,
                nb_spam INT DEFAULT NULL,
                nb_virus INT DEFAULT NULL,
                nb_authorized INT DEFAULT NULL,
                nb_banned INT DEFAULT NULL,
                nb_deleted INT DEFAULT NULL,
                nb_restored INT DEFAULT NULL,
                domain_id INT NOT NULL,
                INDEX IDX_64BEE0B4115F0EE5 (domain_id),
                PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4
                COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '';
            SQL);
        $this->addSql(<<<SQL
            ALTER TABLE daily_stat
            ADD CONSTRAINT `FK_64BEE0B4115F0EE5`
            FOREIGN KEY (domain_id)
            REFERENCES domain (id)
            ON DELETE CASCADE;
        SQL);
    }
}
