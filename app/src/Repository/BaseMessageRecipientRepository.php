<?php

namespace App\Repository;

use App\Amavis\ContentType;
use App\Amavis\DeliveryStatus;
use App\Amavis\MessageStatus;
use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Msgrcpt;
use App\Entity\OutMsgrcpt;
use App\Util\Search;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template T of Msgrcpt|OutMsgrcpt
 * @extends BaseRepository<T>
 */
abstract class BaseMessageRecipientRepository extends BaseRepository
{
    abstract protected function getBaseQueryBuilder(): QueryBuilder;

    /**
     * @param ?array<string, mixed> $filters
     */
    public function getAdvancedSearchQuery(
        ?array $filters = [],
    ): Query {

        $queryBuilder = $this->getBaseQueryBuilder();

        if (isset($filters['host'])) {
            $queryBuilder->andWhere('m.host like :host');
            $queryBuilder->setParameter('host', '%' . $filters['host'] . '%');
        }

        $subjectSearch = Search::textToMariadbBooleanSearch($filters['subject'] ?? '');

        if ($subjectSearch) {
            $queryBuilder->andWhere('MATCH(m.subject) AGAINST(:searchKey BOOLEAN) > 0');
            $queryBuilder->setParameter('searchKey', $subjectSearch);
        }

        if (isset($filters['fromAddr'])) {
            $queryBuilder->andWhere('m.fromAddr like :fromAddr');
            $queryBuilder->setParameter('fromAddr', '%' . $filters['fromAddr'] . '%');
        }

        if (isset($filters['email'])) {
            $queryBuilder->andWhere('maddr.email like :email');
            $queryBuilder->setParameter('email', '%' . $filters['email'] . '%');
        }

        if (isset($filters['mailId'])) {
            $queryBuilder->andWhere('mr.mailId like :mailId');
            $queryBuilder->setParameter('mailId', '%' . $filters['mailId'] . '%');
        }

        $this->addDateCondition($queryBuilder, $filters);
        $this->addReplyToCondition($queryBuilder, $filters);
        $this->addSpamLevelCondition($queryBuilder, $filters);
        $this->addSizeCondition($queryBuilder, $filters);

        $query = $queryBuilder->getQuery();

        $countQueryBuilder = clone $queryBuilder;
        $countQueryBuilder->select('COUNT(mr.mailId)');
        $countQuery = $countQueryBuilder->getQuery();
        $query->setHint('knp_paginator.count', $countQuery->getSingleScalarResult());

        return $query;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function addDateCondition(QueryBuilder $queryBuilder, ?array $filters = []): void
    {
        if (isset($filters['startDate']) && isset($filters['endDate'])) {
            $queryBuilder->andWhere('m.timeNum BETWEEN :startDate AND :endDate');
            $queryBuilder->setParameter('startDate', $filters['startDate']->getTimestamp());
            $queryBuilder->setParameter('endDate', $filters['endDate']->getTimestamp());
        }

        if (isset($filters['startDate']) && !isset($filters['endDate'])) {
            $queryBuilder->andWhere('m.timeNum >= :startDate');
            $queryBuilder->setParameter('startDate', $filters['startDate']->getTimestamp());
        }

        if (!isset($filters['startDate']) && isset($filters['endDate'])) {
            $queryBuilder->andWhere('m.timeNum <= :endDate');
            $queryBuilder->setParameter('endDate', $filters['endDate']->getTimestamp());
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function addReplyToCondition(QueryBuilder $queryBuilder, ?array $filters = []): void
    {
        if (isset($filters['replyTo']) && $filters['replyTo'] === 'non') {
            $queryBuilder->andWhere('m.subject not like \'Re:%\' AND m.subject NOT LIKE \'RE:%\'');
        }

        if (isset($filters['replyTo']) && $filters['replyTo'] === 'oui') {
            $queryBuilder->andWhere('m.subject like \'Re:%\' OR m.subject LIKE \'RE:%\'');
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function addSpamLevelCondition(QueryBuilder $queryBuilder, ?array $filters = []): void
    {
        if (isset($filters['bspamLevelMin']) && !isset($filters['bspamLevelMax'])) {
            $queryBuilder->andWhere('mr.bspamLevel >= :bspamLevelMin');
            $queryBuilder->setParameter('bspamLevelMin', $filters['bspamLevelMin']);
        }

        if (!isset($filters['bspamLevelMin']) && isset($filters['bspamLevelMax'])) {
            $queryBuilder->andWhere('mr.bspamLevel <= :bspamLevelMax');
            $queryBuilder->setParameter('bspamLevelMax', $filters['bspamLevelMax']);
        }

        if (isset($filters['bspamLevelMin']) && isset($filters['bspamLevelMax'])) {
            $queryBuilder->andWhere('mr.bspamLevel BETWEEN :bspamLevelMin AND :bspamLevelMax');
            $queryBuilder->setParameter('bspamLevelMin', $filters['bspamLevelMin']);
            $queryBuilder->setParameter('bspamLevelMax', $filters['bspamLevelMax']);
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function addSizeCondition(QueryBuilder $queryBuilder, ?array $filters = []): void
    {
        if (isset($filters['sizeMin']) && !isset($filters['sizeMax'])) {
            $queryBuilder->andWhere('m.size >= :sizeMin');
            $queryBuilder->setParameter('sizeMin', $filters['sizeMin']);
        }

        if (!isset($filters['sizeMin']) && isset($filters['sizeMax'])) {
            $queryBuilder->andWhere('m.size <= :sizeMax');
            $queryBuilder->setParameter('sizeMax', $filters['sizeMax']);
        }

        if (isset($filters['sizeMin']) && isset($filters['sizeMax'])) {
            $queryBuilder->andWhere('m.size BETWEEN :sizeMin AND :sizeMax');
            $queryBuilder->setParameter('sizeMin', $filters['sizeMin']);
            $queryBuilder->setParameter('sizeMax', $filters['sizeMax']);
        }
    }
}
