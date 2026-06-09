<?php

namespace App\Repository;

use App\Entity\Policy;
use Doctrine\ORM\Query;
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

    /**
     * @return Query<Policy>
     */
    public function getSearchQuery(string $search): Query
    {
        return $this->createQueryBuilder('p')
            ->where('p.policyName LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->getQuery();
    }
}
