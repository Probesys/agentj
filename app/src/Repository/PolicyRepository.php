<?php

namespace App\Repository;

use App\Entity\Policy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Cocur\Slugify\Slugify;

/**
 * @method Policy|null find($id, $lockMode = null, $lockVersion = null)
 * @method Policy|null findOneBy(array $criteria, array $orderBy = null)
 * @method Policy[]    findAll()
 * @method Policy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PolicyRepository extends ServiceEntityRepository {

    public function __construct(RegistryInterface $registry) {
        parent::__construct($registry, Policy::class);
    }

    
}
