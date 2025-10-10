<?php

namespace App\Repository;

use App\Entity\Alert;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Alert>
 */
class AlertRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alert::class);
    }
}
