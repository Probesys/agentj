<?php

namespace App\Repository;

use App\Entity\ImapConnector;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<ImapConnector>
 */
class ImapConnectorRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImapConnector::class);
    }
}
