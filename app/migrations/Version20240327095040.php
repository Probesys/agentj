<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
// phpcs:disable Generic.Files.LineLength
final class Version20240327095040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dkim table to save domain keys and allow opendkim container to read them';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dkim (id INT AUTO_INCREMENT NOT NULL, domain_name VARCHAR(255) NOT NULL, selector VARCHAR(255) NOT NULL, private_key LONGTEXT NOT NULL, public_key LONGTEXT NOT NULL, INDEX dkim_idx_domain (domain_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE domain ADD domain_keys_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE domain ADD CONSTRAINT FK_A7A91E0BBA7BD17E FOREIGN KEY (domain_keys_id) REFERENCES dkim (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A7A91E0BBA7BD17E ON domain (domain_keys_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain DROP FOREIGN KEY FK_A7A91E0BBA7BD17E');
        $this->addSql('DROP TABLE dkim');
        $this->addSql('DROP INDEX UNIQ_A7A91E0BBA7BD17E ON domain');
        $this->addSql('ALTER TABLE domain DROP domain_keys_id');
    }
}
