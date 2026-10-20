<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\SenderRule;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019210121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Apply the domain priority on sender rules of domain users, keeping the rule applied by Amavis';
    }

    public function up(Schema $schema): void
    {
        $parameters = [
            'priorityDomain' => SenderRule::PRIORITY_DOMAIN,
            'priorityUser' => SenderRule::PRIORITY_USER,
        ];

        // If the domain rules are duplicated with different priorities, keep
        // the ones that have the highest priority (aka PRIORITY_USER).
        $this->addSql(<<<'SQL'
            DELETE discarded
            FROM wblist discarded
            INNER JOIN users u ON u.id = discarded.rid
            INNER JOIN wblist kept ON (
                kept.rid = discarded.rid
                AND kept.sid = discarded.sid
                AND kept.priority = :priorityUser
            )
            WHERE u.email LIKE '@%'
            AND discarded.priority = :priorityDomain
        SQL, $parameters);

        // Then update the priorities to make sure they are coherent (i.e.
        // PRIORITY_DOMAIN)
        $this->addSql(<<<'SQL'
            UPDATE wblist sr
            INNER JOIN users u ON u.id = sr.rid
            SET sr.priority = :priorityDomain
            WHERE u.email LIKE '@%'
            AND sr.priority = :priorityUser
        SQL, $parameters);
    }

    public function down(Schema $schema): void
    {
        // Do nothing on purpose.
    }
}
