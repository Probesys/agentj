<?php

namespace App\Repository;

use App\Entity\SenderRateLimit;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SenderRateLimit>
 */
class SenderRateLimitRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SenderRateLimit::class);
    }
}
