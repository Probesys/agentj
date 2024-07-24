<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240724142425_update_domain extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update domain table : remove imap infos';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain DROP srv_imap, DROP imap_port, DROP imap_flag');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain ADD srv_imap VARCHAR(255) NOT NULL, ADD imap_port INT DEFAULT 143 NOT NULL, ADD imap_flag VARCHAR(255) NOT NULL');
    }
}
