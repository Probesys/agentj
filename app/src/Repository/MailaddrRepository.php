<?php

namespace App\Repository;

use App\Entity\Mailaddr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Mailaddr>
 */
class MailaddrRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mailaddr::class);
    }
}
