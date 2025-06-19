<?php

namespace App\Repository;

use App\Amavis\ContentType;
use App\Entity\User;
use App\Entity\MessageStatus;
use App\Amavis\DeliveryStatus;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class MsgrcptSearchRepository extends MsgrcptRepository
{
    /**
     * @param ?array{sort: string, direction: string} $sortParams
     */
    public function getSearchQuery(
        User $user,
        ?int $messageStatus = null,
        ?string $searchKey = null,
        ?array $sortParams = null,
        ?int $fromDate = null
    ): Query {


        $qb = $this->getSearchQueryBuilder($user, $messageStatus);
        $this->addSearchKeyCondition($qb, $searchKey);

        if (!is_null($fromDate)) {
            $qb->andWhere('m.timeNum > ' . $fromDate);
        }

        if ($sortParams) {
            $qb->orderBy($sortParams['sort'], $sortParams['direction']);
        }

        $query = $qb->getQuery();

        return $query;
    }

    public function countByType(?User $user = null, ?int $type = null): int
    {
        $messageStatus = null;
        if ($type) {
            $entityManager = $this->getEntityManager();
            $messageStatus = $entityManager->getRepository(MessageStatus::class)->find($type);
        }
        $query = $this->getSearchQuery($user, $messageStatus);
        $paginator = new Paginator($query);

        return $paginator->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByTypeAndDays(?User $user = null, ?int $type = null): array
    {
        $messageStatus = null;
        if ($type) {
            $entityManager = $this->getEntityManager();
            $messageStatus = $entityManager->getRepository(MessageStatus::class)->find($type);
        }
        $qb = $this->getSearchQueryBuilder($user, $messageStatus);
        $qb->select('COUNT(mr.mailId) as nb_result, m.timeIso, SUBSTRING(m.timeIso, 1, 8) as date_group');
        $qb->groupBy('date_group');

        $query = $qb->getQuery();

        return $query->getScalarResult();
    }

    private function getSearchQueryBuilder(
        User $user,
        ?MessageStatus $messageStatus = null
    ): QueryBuilder {

        $qb = $this->createQueryBuilder('mr');
        $qb->select('mr')
        ->leftJoin('App\Entity\Msgs', 'm', Join::WITH, 'm.mailId = mr.mailId AND m.partitionTag = mr.partitionTag')
        ->leftJoin('App\Entity\Maddr', 'maddr', Join::WITH, 'maddr.id = mr.rid');

        $this->addUserSpecificJoins($qb, $user);

        if ($user->isAdmin()) {
            $this->addDomainCondition($qb, $user);
        } else {
            $this->addRecipientsCondition($qb, $user);
        }

        if (!$messageStatus) {
            $this->addSpamCondition($qb, $user, false);
        } elseif ($messageStatus->getId() === MessageStatus::SPAMMED) {
            $this->addSpamCondition($qb, $user, true);
        }

        $this->addStatusCondition($qb, $messageStatus);

        return $qb;
    }

    private function addUserSpecificJoins(QueryBuilder $qb, User $user): void
    {
        if ($user->isAdmin()) {
            $qb->leftJoin('App\Entity\User', 'u', Join::WITH, 'u.email = maddr.email')
               ->leftJoin('App\Entity\Domain', 'd', Join::WITH, 'd.id = u.domain');
        }
    }

    private function addStatusCondition(QueryBuilder $qb, ?MessageStatus $messageStatus): void
    {
        if (!$messageStatus) {
            $messagestatusError = $this->getEntityManager()->getRepository(MessageStatus::class)->find(MessageStatus::ERROR);
            $qb->andWhere('( mr.status IS NULL  OR mr.status = :messagestatusError ) and mr.bl!=\'Y\' and mr.content not in (:content) and mr.ds != :ds')
                ->setParameter('messagestatusError', $messagestatusError)
                ->setParameter('content', [ContentType::Virus, ContentType::Clean])
                ->setParameter('ds', DeliveryStatus::Pass);
        }

        if ($messageStatus?->getId() === MessageStatus::DELETED) {
            $qb->andWhere('mr.status = :messageStatus and mr.content != :content')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('content', ContentType::Virus);
        }

        if ($messageStatus?->getId() === MessageStatus::RESTORED) {
            $qb->andWhere('mr.status = :messageStatus and mr.content != :content')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('content', ContentType::Virus);
        }

        if ($messageStatus?->getId() === MessageStatus::AUTHORIZED) {
            $qb->andWhere('mr.status = :messageStatus or (mr.ds = :ds and (mr.status is null or mr.status = :messageStatus))')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('ds', DeliveryStatus::Pass);
        }

        if ($messageStatus?->getId() === MessageStatus::BANNED) {
            $qb->andWhere('mr.status = :messageStatus or mr.bl = :bl')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('bl', 'Y');
        }

        if ($messageStatus?->getId() === MessageStatus::SPAMMED) {
            $qb->andWhere('mr.status is null and mr.content not in (:content)')
                ->setParameter('content', [ContentType::Virus, ContentType::Clean]);
        }

        if ($messageStatus?->getId() === MessageStatus::VIRUS) {
            $qb->andWhere('mr.content = :content')
                ->setParameter('content', ContentType::Virus);
        }

        if ($messageStatus?->getId() === MessageStatus::ERROR) {
            $qb->andWhere('mr.status = :messageStatus and mr.content not in (:content)')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('content', [ContentType::Virus, ContentType::Clean]);
        }
    }

    private function addSpamCondition(QueryBuilder $qb, User $user, bool $isSpamm): void
    {
        $comparisonOperator = $isSpamm ? '>' : '<=';

        if ($user->isAdmin()) {
            $qb->andWhere('mr.bspamLevel ' . $comparisonOperator . ' d.level');
        } else {
            $level = $user->getDomain()->getLevel();
            $qb->andWhere('mr.bspamLevel ' . $comparisonOperator . ' :level')
                ->setParameter('level', $level);
        }
    }

    private function addRecipientsCondition(QueryBuilder $qb, User $user): void
    {
        $aliases = $user->getAliases()->toArray();
        $recipients = array_map(function (User $alias) {
            return $alias->getEmailFromRessource();
        }, $aliases);
        $recipients[] = $user->getEmailFromRessource();
        $qb->andWhere('maddr.email IN (:recipients)')
            ->setParameter('recipients', $recipients);
    }

    private function addSearchKeyCondition(QueryBuilder $qb, ?string $searchKey): void
    {
        if ($searchKey) {
            $qb->andWhere('m.subject LIKE :searchKey OR maddr.email LIKE :searchKey OR m.fromAddr LIKE :searchKey')
                ->setParameter('searchKey', '%' . $searchKey . '%');
        }
    }

    private function addDomainCondition(QueryBuilder $qb, User $user): void
    {
        if (count($user->getDomains()) > 0) {
            $qb->andWhere('u.domain in (:domain)')
                ->setParameter('domain', $user->getDomains());
        }
    }
}
