<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20260506133939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the SpamAssassin Bayes tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bayes_global_vars (variable VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, value VARCHAR(200) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, PRIMARY KEY(variable)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE bayes_vars (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(200) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, spam_count INT DEFAULT 0 NOT NULL, ham_count INT DEFAULT 0 NOT NULL, token_count INT DEFAULT 0 NOT NULL, last_expire INT DEFAULT 0 NOT NULL, last_atime_delta INT DEFAULT 0 NOT NULL, last_expire_reduce INT DEFAULT 0 NOT NULL, oldest_token_age INT DEFAULT 2147483647 NOT NULL, newest_token_age INT DEFAULT 0 NOT NULL, UNIQUE INDEX bayes_vars_idx1 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE bayes_seen (id INT DEFAULT 0 NOT NULL, msgid VARCHAR(200) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_bin`, flag CHAR(1) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, PRIMARY KEY(id, msgid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE bayes_expire (id INT DEFAULT 0 NOT NULL, runtime INT DEFAULT 0 NOT NULL, INDEX bayes_expire_idx1 (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE bayes_token (id INT DEFAULT 0 NOT NULL, token BINARY(5) DEFAULT \'' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '\' NOT NULL, spam_count INT DEFAULT 0 NOT NULL, ham_count INT DEFAULT 0 NOT NULL, atime INT DEFAULT 0 NOT NULL, INDEX bayes_token_idx1 (id, atime), PRIMARY KEY(id, token)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');

        $this->addSql('INSERT INTO bayes_global_vars VALUES (\'VERSION\',\'3\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE bayes_global_vars');
        $this->addSql('DROP TABLE bayes_vars');
        $this->addSql('DROP TABLE bayes_seen');
        $this->addSql('DROP TABLE bayes_expire');
        $this->addSql('DROP TABLE bayes_token');
    }
}
