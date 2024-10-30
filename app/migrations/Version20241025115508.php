<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20241025115508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add send_user_alerts and send_user_mail_alerts column to domain table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain ADD COLUMN send_user_alerts BOOLEAN DEFAULT FALSE');
        $this->addSql('ALTER TABLE domain ADD COLUMN send_user_mail_alerts BOOLEAN DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain DROP COLUMN send_user_alerts');
        $this->addSql('ALTER TABLE domain DROP COLUMN send_user_mail_alerts');
    }
}
