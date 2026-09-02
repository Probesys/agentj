<?php

namespace App\Repository;

use App\Entity\OutMsgrcpt;
use App\Entity\User;
use App\Repository\BaseMessageRecipientRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends BaseMessageRecipientRepository<OutMsgrcpt>
 */
class OutMsgrcptRepository extends BaseMessageRecipientRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutMsgrcpt::class);
    }

    protected function getBaseQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('mr');
        $queryBuilder->select('mr')
            ->leftJoin(
                'App\Entity\OutMsg',
                'm',
                Join::WITH,
                'm.mailId = mr.mailId AND m.partitionTag = mr.partitionTag'
            )
            ->leftJoin('App\Entity\Maddr', 'maddr', Join::WITH, 'maddr.id = mr.rid');

        return $queryBuilder;
    }

    protected function applyDomainRestriction(QueryBuilder $queryBuilder, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $queryBuilder->innerJoin('m.sid', 'sa');
        $queryBuilder->innerJoin('App\Entity\User', 'u', Join::WITH, 'u.email = sa.email');
        $queryBuilder->andWhere('u.domain in (:restrictedDomains)');
        $queryBuilder->setParameter('restrictedDomains', $user->getDomains());
    }
}
