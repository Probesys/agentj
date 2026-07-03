<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260706134706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename Groups entity to Group and update table attributes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector_groups DROP FOREIGN KEY `FK_262FEFCFF373DCF`');
        $this->addSql('DROP INDEX IDX_262FEFCFF373DCF ON connector_groups');
        $this->addSql(<<<SQL
            ALTER TABLE connector_groups
            CHANGE groups_id group_id INT NOT NULL,
            DROP PRIMARY KEY,
            ADD PRIMARY KEY (connector_id, group_id);
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE connector_groups
            ADD CONSTRAINT FK_262FEFCFFE54D947
                FOREIGN KEY (group_id)
                REFERENCES groups (id)
                ON DELETE CASCADE
            ;
        SQL);
        $this->addSql('CREATE INDEX IDX_262FEFCFFE54D947 ON connector_groups (group_id)');
        $this->addSql('ALTER TABLE rights_groups DROP FOREIGN KEY `FK_C05A1BCCF373DCF`');
        $this->addSql('DROP INDEX IDX_C05A1BCCF373DCF ON rights_groups');
        $this->addSql(<<<SQL
            ALTER TABLE rights_groups
            CHANGE groups_id group_id INT NOT NULL,
            DROP PRIMARY KEY,
            ADD PRIMARY KEY (rights_id, group_id);
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE rights_groups
            ADD CONSTRAINT FK_C05A1BCCFE54D947
                FOREIGN KEY (group_id)
                REFERENCES groups (id)
                ON DELETE CASCADE
            ;
        SQL);
        $this->addSql('CREATE INDEX IDX_C05A1BCCFE54D947 ON rights_groups (group_id)');
        $this->addSql('ALTER TABLE user_groups DROP FOREIGN KEY `FK_953F224DF373DCF`');
        $this->addSql(<<<SQL
            ALTER TABLE user_groups
            ADD CONSTRAINT FK_953F224DF373DCF
                FOREIGN KEY (groups_id)
                REFERENCES groups (id)
            ;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connector_groups DROP FOREIGN KEY FK_262FEFCFFE54D947');
        $this->addSql('DROP INDEX IDX_262FEFCFFE54D947 ON connector_groups');
        $this->addSql(<<<SQL
            ALTER TABLE connector_groups
            CHANGE group_id groups_id INT NOT NULL,
            DROP PRIMARY KEY,
            ADD PRIMARY KEY (connector_id, groups_id);
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE connector_groups
            ADD CONSTRAINT `FK_262FEFCFF373DCF`
                FOREIGN KEY (groups_id)
                REFERENCES groups (id)
                ON DELETE CASCADE
            ;
        SQL);
        $this->addSql('CREATE INDEX IDX_262FEFCFF373DCF ON connector_groups (groups_id)');
        $this->addSql('ALTER TABLE rights_groups DROP FOREIGN KEY FK_C05A1BCCFE54D947');
        $this->addSql('DROP INDEX IDX_C05A1BCCFE54D947 ON rights_groups');
        $this->addSql(<<<SQL
            ALTER TABLE rights_groups
            CHANGE group_id groups_id INT NOT NULL,
            DROP PRIMARY KEY,
            ADD PRIMARY KEY (rights_id, groups_id);
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE rights_groups
            ADD CONSTRAINT `FK_C05A1BCCF373DCF`
                FOREIGN KEY (groups_id)
                REFERENCES groups (id)
                ON DELETE CASCADE
            ;
        SQL);
        $this->addSql('CREATE INDEX IDX_C05A1BCCF373DCF ON rights_groups (groups_id)');
        $this->addSql('ALTER TABLE user_groups DROP FOREIGN KEY FK_953F224DF373DCF');
        $this->addSql(<<<SQL
            ALTER TABLE user_groups
            ADD CONSTRAINT `FK_953F224DF373DCF`
                FOREIGN KEY (groups_id)
                REFERENCES groups (id)
                ON DELETE CASCADE
            ;
        SQL);
    }
}
