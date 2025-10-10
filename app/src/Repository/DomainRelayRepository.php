<?php

namespace App\Repository;

use App\Entity\DomainRelay;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<DomainRelay>
 */
class DomainRelayRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainRelay::class);
    }
}
