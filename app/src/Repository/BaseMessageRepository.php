<?php

namespace App\Repository;

use App\Entity\Msgs;
use App\Entity\OutMsg;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template T of Msgs|OutMsg
 * @extends ServiceEntityRepository<T>
 */
abstract class BaseMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }
}
