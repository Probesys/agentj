<?php

namespace App\Repository;

use App\Amavis\ContentType;
use App\Entity\Domain;
use App\Entity\User;
use App\Amavis\MessageStatus;
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
        ?User $user,
        ?int $messageStatus = null,
        ?string $searchKey = null,
        ?array $sortParams = null,
        ?int $fromDate = null
    ): Query {


        $queryBuilder = $this->getSearchQueryBuilder($user, $messageStatus);

        if ($searchKey) {
            $this->addSearchKeyCondition($queryBuilder, $searchKey);
        }

        if (!is_null($fromDate)) {
            $queryBuilder->andWhere('m.timeNum > :date');
            $queryBuilder->setParameter('date', $fromDate);
        }

        if ($sortParams) {
            $queryBuilder->orderBy($sortParams['sort'], $sortParams['direction']);
        }

        $query = $queryBuilder->getQuery();

        return $query;
    }

    public function countByType(?User $user = null, ?int $messageStatus = null): int
    {
        $query = $this->getSearchQuery($user, $messageStatus);
        $paginator = new Paginator($query);

        return $paginator->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByTypeAndDays(
        ?Domain $domain = null,
        ?int $fromDate = null,
        ?int $messageStatus = null,
        ?User $user = null
    ): array {
        $queryBuilder = $this->getSearchQueryBuilder($user, $messageStatus);
        $queryBuilder->select('COUNT(mr.mailId) as nb_result, m.timeIso, SUBSTRING(m.timeIso, 1, 8) as date_group');

        if (!is_null($fromDate)) {
            $queryBuilder->andWhere('m.timeNum >= :date');
            $queryBuilder->setParameter('date', $fromDate);
        }

        if ($domain) {
            $queryBuilder->andWhere('d.id = :domain');
            $queryBuilder->setParameter('domain', $domain->getId());
        }

        $queryBuilder->groupBy('date_group');

        $query = $queryBuilder->getQuery();

        return $query->getScalarResult();
    }

    private function getSearchQueryBuilder(
        ?User $user,
        ?int $messageStatus = null
    ): QueryBuilder {

        $queryBuilder = $this->createQueryBuilder('mr');
        $queryBuilder->select('mr')
            ->leftJoin('App\Entity\Msgs', 'm', Join::WITH, 'm.mailId = mr.mailId AND m.partitionTag = mr.partitionTag')
            ->leftJoin('App\Entity\Maddr', 'maddr', Join::WITH, 'maddr.id = mr.rid');

        $this->addUserSpecificJoins($queryBuilder, $user);

        if ($user?->isAdmin()) {
            $this->addDomainCondition($queryBuilder, $user);
        } elseif ($user) {
            $this->addRecipientsCondition($queryBuilder, $user);
        }

        if ($messageStatus === MessageStatus::UNTREATED) {
            $this->addSpamCondition($queryBuilder, $user, isSpam: false);
        } elseif ($messageStatus === MessageStatus::SPAMMED) {
            $this->addSpamCondition($queryBuilder, $user, isSpam: true);
        }

        $this->addStatusCondition($queryBuilder, $messageStatus);

        return $queryBuilder;
    }

    private function addUserSpecificJoins(QueryBuilder $queryBuilder, ?User $user): void
    {
        if (!$user || $user->isAdmin()) {
            $queryBuilder->leftJoin('App\Entity\User', 'u', Join::WITH, 'u.email = maddr.email')
               ->leftJoin('App\Entity\Domain', 'd', Join::WITH, 'd.id = u.domain');
        }
    }

    private function addStatusCondition(QueryBuilder $queryBuilder, ?int $messageStatus): void
    {
        if ($messageStatus === MessageStatus::UNTREATED) {
            $queryBuilder
                ->andWhere(<<<SQL
                    (
                        mr.status IS NULL
                        OR mr.status = :messagestatusError
                    )
                    AND mr.bl != 'Y'
                    AND mr.content NOT IN (:content)
                    AND mr.ds != :ds
                SQL)
                ->setParameter('messagestatusError', MessageStatus::ERROR)
                ->setParameter('content', [ContentType::VIRUS, ContentType::CLEAN])
                ->setParameter('ds', DeliveryStatus::PASS);
        }

        if ($messageStatus === MessageStatus::DELETED) {
            $queryBuilder->andWhere('mr.status = :messageStatus and mr.content != :content')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('content', ContentType::VIRUS);
        }

        if ($messageStatus === MessageStatus::RESTORED) {
            $queryBuilder->andWhere('mr.status = :messageStatus and mr.content != :content')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('content', ContentType::VIRUS);
        }

        if ($messageStatus === MessageStatus::AUTHORIZED) {
            $queryBuilder
                ->andWhere(<<<SQL
                    mr.status = :messageStatus
                    or (
                        mr.ds = :ds
                        and (mr.status is null or mr.status = :messageStatus)
                    )
                SQL)
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('ds', DeliveryStatus::PASS);
        }

        if ($messageStatus === MessageStatus::BANNED) {
            $queryBuilder->andWhere('mr.status = :messageStatus or mr.bl = :bl')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('bl', 'Y');
        }

        if ($messageStatus === MessageStatus::SPAMMED) {
            $queryBuilder->andWhere('mr.status is null and mr.content not in (:content)')
                ->setParameter('content', [ContentType::VIRUS, ContentType::CLEAN]);
        }

        if ($messageStatus === MessageStatus::VIRUS) {
            $queryBuilder->andWhere('mr.content = :content')
                ->setParameter('content', ContentType::VIRUS);
        }

        if ($messageStatus === MessageStatus::ERROR) {
            $queryBuilder->andWhere('mr.status = :messageStatus and mr.content not in (:content)')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('content', [ContentType::VIRUS, ContentType::CLEAN]);
        }
    }

    private function addSpamCondition(QueryBuilder $queryBuilder, ?User $user, bool $isSpam): void
    {
        $comparisonOperator = $isSpam ? '>' : '<=';

        if (!$user || $user->isAdmin()) {
            $queryBuilder->andWhere('mr.bspamLevel ' . $comparisonOperator . ' d.level');
        } else {
            $level = $user->getDomain()->getLevel();
            $queryBuilder->andWhere('mr.bspamLevel ' . $comparisonOperator . ' :level')
                ->setParameter('level', $level);
        }
    }

    private function addRecipientsCondition(QueryBuilder $queryBuilder, User $user): void
    {
        $aliases = $user->getAliases()->toArray();
        $recipients = array_map(function (User $alias) {
            return $alias->getEmailFromRessource();
        }, $aliases);
        $recipients[] = $user->getEmailFromRessource();
        $queryBuilder->andWhere('maddr.email IN (:recipients)')
            ->setParameter('recipients', $recipients);
    }

    private function addSearchKeyCondition(QueryBuilder $queryBuilder, string $searchKey): void
    {
        $queryBuilder
            ->andWhere('m.subject LIKE :searchKey OR maddr.email LIKE :searchKey OR m.fromAddr LIKE :searchKey')
            ->setParameter('searchKey', '%' . $searchKey . '%');
    }

    private function addDomainCondition(QueryBuilder $queryBuilder, User $user): void
    {
        if (count($user->getDomains()) > 0) {
            $queryBuilder->andWhere('u.domain in (:domain)')
                ->setParameter('domain', $user->getDomains());
        }
    }
}
