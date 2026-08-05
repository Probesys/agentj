<?php

namespace App\Repository;

use App\Entity\Right;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Right>
 */
class RightRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Right::class);
    }
}
