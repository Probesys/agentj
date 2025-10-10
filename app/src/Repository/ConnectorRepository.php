<?php

namespace App\Repository;

use App\Entity\Connector;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Connector>
 */
class ConnectorRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Connector::class);
    }
}
