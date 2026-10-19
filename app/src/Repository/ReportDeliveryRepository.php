<?php

namespace App\Repository;

use App\Entity\ReportDelivery;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

/**
 * @extends BaseRepository<ReportDelivery>
 */
class ReportDeliveryRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReportDelivery::class);
    }

    public function hasPending(User $user): bool
    {
        return $this->count(['user' => $user]) > 0;
    }

    public function findForSending(int $id): ?ReportDelivery
    {
        return $this->createQueryBuilder('report')
            ->addSelect('user', 'domain')
            ->innerJoin('report.user', 'user')
            ->innerJoin('user.domain', 'domain')
            ->where('report.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    public function complete(ReportDelivery $report, int $sentAt): void
    {
        $em = $this->getEntityManager();
        $em->getConnection()->transactional(function ($connection) use ($report, $sentAt): void {
            $connection->executeStatement(<<<SQL
                UPDATE users
                SET report_period_end = GREATEST(COALESCE(report_period_end, date_last_report, 0), :periodEnd),
                    date_last_report = GREATEST(COALESCE(date_last_report, 0), :sentAt)
                WHERE id = :user
            SQL, [
                'periodEnd' => $report->getPeriodEnd(),
                'sentAt' => $sentAt,
                'user' => $report->getUser()->getId(),
            ]);
            $connection->delete('report_delivery', ['id' => $report->getId()]);
        });
        $em->detach($report);
        $em->refresh($report->getUser());
    }
}
