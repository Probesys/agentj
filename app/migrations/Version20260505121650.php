<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505121650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add an index on send_captcha and time_num columns of msgs table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX msgs_idx_send_captcha_time_num ON msgs (send_captcha, time_num)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX msgs_idx_send_captcha_time_num ON msgs');
    }
}
