<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230118130123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE daily_stat ADD domain_id INT NOT NULL');
        $this->addSql('ALTER TABLE daily_stat ADD CONSTRAINT FK_64BEE0B4115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('CREATE INDEX IDX_64BEE0B4115F0EE5 ON daily_stat (domain_id)');
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \' \' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE msgs CHANGE originating originating CHAR(1) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE daily_stat DROP FOREIGN KEY FK_64BEE0B4115F0EE5');
        $this->addSql('DROP INDEX IDX_64BEE0B4115F0EE5 ON daily_stat');
        $this->addSql('ALTER TABLE daily_stat DROP domain_id');
    }
}
