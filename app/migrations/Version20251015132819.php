<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20251015132819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_synchronized_at and last_result_synchronization columns to connector table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE connector ADD last_synchronized_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            ADD last_result_synchronization LONGTEXT DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector DROP last_synchronized_at, DROP last_result_synchronization');
    }
}
