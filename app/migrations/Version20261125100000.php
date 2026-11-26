<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261125100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Apply the rules created by sent emails to the original users and all their aliases.';
    }

    public function up(Schema $schema): void
    {
        // The rules created by amavis when an email is sent (type 4) only
        // applied to the address used to send the email.
        $this->addSql(<<<SQL
            INSERT INTO wblist (rid, sid, wb, type, priority)
            SELECT DISTINCT u.id, w.sid, ' ', 4, 100
            FROM wblist w
            JOIN users s ON s.id = w.rid
            JOIN users u ON u.id = COALESCE(s.original_user_id, s.id)
                OR u.original_user_id = COALESCE(s.original_user_id, s.id)
            WHERE w.type = 4
            AND NOT EXISTS (SELECT 1 FROM wblist w2 WHERE w2.rid = u.id AND w2.sid = w.sid)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // The copied rules cannot be distinguished from the ones created by
        // amavis: they are kept.
    }
}
