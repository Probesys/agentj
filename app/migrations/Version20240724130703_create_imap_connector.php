<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Alter table connector to add imap info
 */
final class Version20240724130703_create_imap_connector extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter table connector to add imap info';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE connector ADD imap_host VARCHAR(255) DEFAULT NULL, ADD imap_port INT DEFAULT NULL, ADD imap_protocol VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE connector DROP imap_host, DROP imap_port, DROP imap_protocol');
    }
}
