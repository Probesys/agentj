<?php

namespace App\Repository;

use App\Entity\Rights;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Rights>
 */
class RightsRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rights::class);
    }
}
