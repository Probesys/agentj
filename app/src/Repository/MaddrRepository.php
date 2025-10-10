<?php

namespace App\Repository;

use App\Entity\Maddr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Maddr>
 */
class MaddrRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Maddr::class);
    }
}
