<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260115151631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix M365 users marked as aliases of themselves.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE users SET original_user_id = null WHERE original_user_id = id');
    }

    public function down(Schema $schema): void
    {
        // Do nothing on purpose
    }
}
