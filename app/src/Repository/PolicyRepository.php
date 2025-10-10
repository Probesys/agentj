<?php

namespace App\Repository;

use App\Entity\Policy;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Policy>
 */
class PolicyRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Policy::class);
    }
}
