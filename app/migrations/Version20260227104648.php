<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227104648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes on msgs and msgrcpt';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX msgrcpt_idx_bspam_level ON msgrcpt (bspam_level)');
        $this->addSql('CREATE INDEX msgs_idx_from_addr ON msgs (from_addr)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX msgrcpt_idx_bspam_level ON msgrcpt');
        $this->addSql('DROP INDEX msgs_idx_from_addr ON msgs');
    }
}
