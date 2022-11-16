<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221110134715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \' \' NOT NULL');
        $this->addSql('ALTER TABLE wblist DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wblist CHANGE priority priority INT NOT NULL');
        $this->addSql('ALTER TABLE wblist ADD PRIMARY KEY (rid, sid, priority)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE wblist DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE wblist CHANGE priority priority INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wblist ADD PRIMARY KEY (rid, sid)');
    }
}
