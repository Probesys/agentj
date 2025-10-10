<?php

namespace App\Repository;

use App\Entity\DailyStat;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<DailyStat>
 */
class DailyStatRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyStat::class);
    }
}
