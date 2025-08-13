<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250826085029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change ondelete constraint for user foreign key on alert table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C1A76ED395');
        $this->addSql('ALTER TABLE alert 
            ADD CONSTRAINT FK_17FD46C1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) 
            ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C1A76ED395');
        $this->addSql('ALTER TABLE alert 
            ADD CONSTRAINT FK_17FD46C1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        ');
    }
}
