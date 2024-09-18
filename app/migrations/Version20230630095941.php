<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230630095941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE daily_stat DROP FOREIGN KEY FK_64BEE0B4115F0EE5');
        $this->addSql('ALTER TABLE daily_stat ADD CONSTRAINT FK_64BEE0B4115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE daily_stat DROP FOREIGN KEY FK_64BEE0B4115F0EE5');
        $this->addSql('ALTER TABLE daily_stat ADD CONSTRAINT FK_64BEE0B4115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
    }
}
