<?php

namespace App\Repository;

use App\Entity\SqlLimitReport;
use App\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SqlLimitReport>
 */
class LimitReportRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SqlLimitReport::class);
    }

    public function countByUser(User $user): int
    {
        $query = $this->getEntityManager()->createQuery(<<<SQL
            SELECT COUNT(slr.id)
            FROM App\Entity\SqlLimitReport slr
            WHERE slr.mailId = :email
        SQL);

        $query->setParameter('email', $user->getEmail());

        return (int) $query->getSingleScalarResult();
    }
}
