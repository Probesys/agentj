<?php

namespace App\Repository;

use App\Entity\Office365Connector;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Office365Connector>
 */
class Office365ConnectorRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Office365Connector::class);
    }
}
