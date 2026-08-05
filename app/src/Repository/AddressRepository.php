<?php

namespace App\Repository;

use App\Entity\Address;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Address>
 */
class AddressRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }
}
