<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230116083821UpdateImapEcnryptionTypes extends AbstractMigration
{
    public function getDescription(): string
    {        
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE domain SET imap_flag = \'ssl\' WHERE imap_flag = \'/imap/ssl\'');
        $this->addSql('UPDATE domain SET imap_flag = \'tls\' WHERE imap_flag = \'/imap/tls\'');
        

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
