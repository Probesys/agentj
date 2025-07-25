<?php

namespace App\Repository;

use App\Amavis\ContentType;
use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Msgrcpt;
use App\Entity\OutMsgrcpt;
use App\Amavis\MessageStatus;
use App\Amavis\DeliveryStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template T of MsgRcpt|OutMsgrcpt
 * @extends ServiceEntityRepository<T>
 */
abstract class BaseMessageRecipientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }


    abstract protected function getBaseQueryBuilder(): QueryBuilder;

    /**
     * @param ?array<string, mixed> $filters
     * @param ?array{sort: string, direction: string} $sortParams
     */
    public function getAdvancedSearchQuery(
        ?array $filters = [],
        ?array $sortParams = null,
    ): Query {

        $queryBuilder = $this->getBaseQueryBuilder();

        if ($sortParams) {
            $queryBuilder->orderBy($sortParams['sort'], $sortParams['direction']);
        }

        if (isset($filters['host'])) {
            $queryBuilder->andWhere('m.host like :host');
            $queryBuilder->setParameter('host', '%' . $filters['host'] . '%');
        }

        if (isset($filters['subject'])) {
            $queryBuilder->andWhere('m.subject like :subject');
            $queryBuilder->setParameter('subject', '%' . $filters['subject'] . '%');
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

        $query = $queryBuilder->getQuery();

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
        if (isset($filters['replyTo']) && $filters['replyTo'] !== 'oui') {
            $queryBuilder->andWhere('m.subject not like \'Re:%\' AND m.subject NOT LIKE \'RE:%\'');
        }

        if (isset($filters['replyTo']) && $filters['replyTo'] !== 'non') {
            $queryBuilder->andWhere('m.subject like \'Re:%\' OR m.subject LIKE \'RE:%\'');
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function addSpamLevelCondition(QueryBuilder $queryBuilder, ?array $filters = []): void
    {
        // dd($filters);
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
}
