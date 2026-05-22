<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260602100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow domain relay to have no associated domain';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain_relay CHANGE domain_id domain_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain_relay CHANGE domain_id domain_id INT NOT NULL');
    }
}
