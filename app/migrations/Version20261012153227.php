<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012153227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add domain_id column to log table and create indexes on domain_id and created columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log ADD domain_id INT DEFAULT NULL');
        $this->addSql(<<<SQL
            ALTER TABLE log
            ADD CONSTRAINT FK_8F3F68C5115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE SET NULL
        SQL);
        $this->addSql('CREATE INDEX IDX_8F3F68C5115F0EE5 ON log (domain_id)');
        $this->addSql('CREATE INDEX log_idx_domain_created ON log (domain_id, created)');
        $this->addSql('CREATE INDEX log_idx_created ON log (created)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log DROP FOREIGN KEY FK_8F3F68C5115F0EE5');
        $this->addSql('DROP INDEX IDX_8F3F68C5115F0EE5 ON log');
        $this->addSql('DROP INDEX log_idx_domain_created ON log');
        $this->addSql('DROP INDEX log_idx_created ON log');
        $this->addSql('ALTER TABLE log DROP domain_id');
    }
}
