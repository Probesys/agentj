<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20231207083955 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add office365_principal_name field in user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD office365_principal_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP office365_principal_name');
    }
}
