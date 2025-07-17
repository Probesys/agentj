<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
// phpcs:disable Squiz.Classes.ValidClassName
// phpcs:disable Generic.Files.LineLength
final class Version20240725090527_create_domain_relay extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création table domain_relay';
    }

    public function up(Schema $schema): void
    {
        // Check if the table already exists
        if (!$schema->hasTable('domain_relay')) {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE domain_relay (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, ip_address VARCHAR(255) NOT NULL, INDEX IDX_8E41CA6F115F0EE5 (domain_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE domain_relay ADD CONSTRAINT FK_8E41CA6F115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        }
    }

    public function down(Schema $schema): void
    {
        // Check if the table exists before attempting to drop it
        if ($schema->hasTable('domain_relay')) {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE domain_relay DROP FOREIGN KEY FK_8E41CA6F115F0EE5');
            $this->addSql('DROP TABLE domain_relay');
        }
    }
}
