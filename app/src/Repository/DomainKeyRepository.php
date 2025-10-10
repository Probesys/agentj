<?php

namespace App\Repository;

use App\Entity\DomainKey;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<DomainKey>
 */
class DomainKeyRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainKey::class);
    }
}
