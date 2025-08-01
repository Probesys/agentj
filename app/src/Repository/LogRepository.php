<?php

namespace App\Repository;

use App\Entity\Log;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Log>
 */
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    /**
     * Truncate entries older than $nbDays from the the log table
     */
    public function truncateOlder(int $nbDays): int
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(<<<SQL
            DELETE App\Entity\Log l
            WHERE l.created <= :date
        SQL);

        $now = new \DateTimeImmutable('now');
        $date = $now->modify("-{$nbDays} days");
        $query->setParameter('date', $date);

        return $query->execute();
    }
}
