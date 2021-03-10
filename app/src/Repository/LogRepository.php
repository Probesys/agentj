<?php

namespace App\Repository;

use App\Entity\Log;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Log|null find($id, $lockMode = null, $lockVersion = null)
 * @method Log|null findOneBy(array $criteria, array $orderBy = null)
 * @method Log[]    findAll()
 * @method Log[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogRepository extends ServiceEntityRepository {

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, Log::class);
  }

  // /**
  //  * @return Log[] Returns an array of Log objects
  //  */
  /*
    public function findByExampleField($value)
    {
    return $this->createQueryBuilder('l')
    ->andWhere('l.exampleField = :val')
    ->setParameter('val', $value)
    ->orderBy('l.id', 'ASC')
    ->setMaxResults(10)
    ->getQuery()
    ->getResult()
    ;
    }
   */

  /*
    public function findOneBySomeField($value): ?Log
    {
    return $this->createQueryBuilder('l')
    ->andWhere('l.exampleField = :val')
    ->setParameter('val', $value)
    ->getQuery()
    ->getOneOrNullResult()
    ;
    }
   */

  /**
   * Truncate entries older than $nbDays from the the log table
   * @param type $nbDays
   */
  public function truncateOlder($nbDays=null) {
    if (!is_null($nbDays)) {
      $conn = $this->getEntityManager()->getConnection();

      $sql = ' delete  from log where DATEDIFF(now(),log.created)> ' . $nbDays;
      $stmt = $conn->prepare($sql);
      $stmt->execute();
    }
  }

}
