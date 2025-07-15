<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20250620143311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove message_status table and its foreign keys';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE msgs DROP FOREIGN KEY FK_5D0FFB2D6BF700BD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE msgrcpt DROP FOREIGN KEY FK_2259F7D46BF700BD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs DROP FOREIGN KEY FK_5D0FFB2D6BF700BD_copy
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgrcpt DROP FOREIGN KEY FK_2259F7D46BF700BD_copy
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE message_status
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_2259F7D46BF700BD ON msgrcpt
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_5D0FFB2D6BF700BD ON msgs
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE message_status (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE msgs ADD CONSTRAINT FK_5D0FFB2D6BF700BD FOREIGN KEY (status_id) REFERENCES message_status (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE msgrcpt ADD CONSTRAINT FK_2259F7D46BF700BD FOREIGN KEY (status_id) REFERENCES message_status (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgs ADD CONSTRAINT FK_5D0FFB2D6BF700BD_copy FOREIGN KEY (status_id) REFERENCES message_status (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE out_msgrcpt ADD CONSTRAINT FK_2259F7D46BF700BD_copy FOREIGN KEY (status_id) REFERENCES message_status (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5D0FFB2D6BF700BD ON msgs (status_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2259F7D46BF700BD ON msgrcpt (status_id)
        SQL);
    }
}
