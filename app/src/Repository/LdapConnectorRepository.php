<?php

namespace App\Repository;

use App\Entity\LdapConnector;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<LdapConnector>
 */
class LdapConnectorRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LdapConnector::class);
    }
}
