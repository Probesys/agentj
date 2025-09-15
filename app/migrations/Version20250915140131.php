<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250915140131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add connector_groups table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE connector_groups (
            connector_id INT NOT NULL,
            groups_id INT NOT NULL,
            INDEX IDX_262FEFCF4D085745 (connector_id),
            INDEX IDX_262FEFCFF373DCF (groups_id),
            PRIMARY KEY(connector_id, groups_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE connector_groups
            ADD CONSTRAINT FK_262FEFCF4D085745
            FOREIGN KEY (connector_id)
            REFERENCES connector (id)
            ON DELETE CASCADE');

        $this->addSql('ALTER TABLE connector_groups
            ADD CONSTRAINT FK_262FEFCFF373DCF
            FOREIGN KEY (groups_id)
            REFERENCES groups (id)
            ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector_groups
            DROP FOREIGN KEY FK_262FEFCF4D085745');
        $this->addSql('ALTER TABLE connector_groups
            DROP FOREIGN KEY FK_262FEFCFF373DCF');
        $this->addSql('DROP TABLE connector_groups');
    }
}
