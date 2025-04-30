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
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'delete from log where DATEDIFF(now(),log.created)> ' . $nbDays;
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();
        return $result->rowCount();
    }
}
