<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use App\Model\ConnectorTypes;

final class Version20240724142306_migrate_imap_info extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'migrate imap info from domain table to connector table';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->connection->fetchAllAssociative('SELECT id, domain, srv_imap, imap_port, imap_flag  FROM domain') as $result) {
            $connector = $this->connection->fetchOne('SELECT id FROM connector where discr=\'Imap\' and domain_id=' . $result['id']);
            if (!$connector){
                $now = new \DateTime();
                $this->connection->insert('connector', [
                    'domain_id' => $result['id'],
                    'name' => 'Imap server ' . $result['domain'],
                    'imap_host' => $result['srv_imap'],
                    'imap_port' => $result['imap_port'],
                    'imap_protocol' => $result['imap_flag'],
                    'discr' => 'Imap',
                    'type' => ConnectorTypes::IMAP,
                    'active' => 1,
                    'created' => $now->format('Y-m-d H:i'),
                    'updated' => $now->format('Y-m-d H:i'),
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
