<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\Log;
use App\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Log>
 */
class LogRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    /**
     * @param array{
     *     startDate?: ?\DateTimeInterface,
     *     endDate?: ?\DateTimeInterface,
     *     actor?: ?string,
     *     action?: ?string,
     *     mailId?: ?string,
     *     domain?: ?Domain,
     * } $filters
     *
     * @return Query<Log>
     */
    public function getSearchQuery(User $user, array $filters = []): Query
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->select('l', 'd', 'u')
            ->leftJoin('l.domain', 'd')
            ->leftJoin('l.createdBy', 'u')
            // Tie-breaker of the sort chosen with the paginator
            ->orderBy('l.id', 'DESC');

        if (!$user->isSuperAdmin()) {
            $queryBuilder->andWhere('l.domain IN (:allowedDomains)');
            $queryBuilder->setParameter('allowedDomains', $user->getDomains());
        }

        $startDate = $filters['startDate'] ?? null;
        if ($startDate !== null) {
            $queryBuilder->andWhere('l.created >= :startDate');
            $queryBuilder->setParameter(
                'startDate',
                \DateTimeImmutable::createFromInterface($startDate)->setTime(0, 0),
            );
        }

        $endDate = $filters['endDate'] ?? null;
        if ($endDate !== null) {
            // The end date is included
            $queryBuilder->andWhere('l.created < :endDate');
            $queryBuilder->setParameter(
                'endDate',
                \DateTimeImmutable::createFromInterface($endDate)->setTime(0, 0)->modify('+1 day'),
            );
        }

        $actor = trim($filters['actor'] ?? '');
        if ($actor !== '') {
            $queryBuilder->andWhere(
                'LOWER(u.email) LIKE LOWER(:actor)'
                . ' OR LOWER(u.username) LIKE LOWER(:actor)'
                . ' OR LOWER(u.fullname) LIKE LOWER(:actor)'
            );
            $queryBuilder->setParameter('actor', "%{$actor}%");
        }

        $action = $filters['action'] ?? null;
        if ($action !== null && $action !== '') {
            $queryBuilder->andWhere('l.action = :action');
            $queryBuilder->setParameter('action', $action);
        }

        $mailId = trim($filters['mailId'] ?? '');
        if ($mailId !== '') {
            $queryBuilder->andWhere('l.mailId = :mailId');
            $queryBuilder->setParameter('mailId', $mailId);
        }

        $domain = $filters['domain'] ?? null;
        if ($domain !== null) {
            $queryBuilder->andWhere('l.domain = :domain');
            $queryBuilder->setParameter('domain', $domain);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * Return the distinct actions of the entries, sorted alphabetically.
     *
     * @return string[]
     */
    public function findActions(): array
    {
        $rows = $this->createQueryBuilder('l')
            ->select('DISTINCT l.action')
            ->where('l.action IS NOT NULL')
            ->orderBy('l.action', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('strval', $rows);
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
