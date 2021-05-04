<?php

namespace App\Repository;

use App\Entity\Rights;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Rights|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rights|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rights[]    findAll()
 * @method Rights[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RightsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rights::class);
    }

    // /**
    //  * @return Rights[] Returns an array of Rights objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Rights
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
