<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241022092208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix policyd-rate-limit mail_count id length';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mail_count MODIFY COLUMN id varchar(320)');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mail_count MODIFY COLUMN id varchar(40)');

    }
}
