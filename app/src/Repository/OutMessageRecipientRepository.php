<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\OutMessage;
use App\Entity\OutMessageRecipient;
use App\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseMessageRecipientRepository<OutMessageRecipient>
 */
class OutMessageRecipientRepository extends BaseMessageRecipientRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutMessageRecipient::class);
    }

    protected function getBaseQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('mr');
        $queryBuilder->select('mr')
            ->leftJoin('mr.message', 'm')
            ->leftJoin('mr.address', 'maddr');

        return $queryBuilder;
    }

    /**
     * @return OutMessageRecipient[]
     */
    public function findByEmailSender(User $user): array
    {
        $query = $this->getEntityManager()->createQuery(<<<SQL
            SELECT omr
            FROM App\Entity\OutMessageRecipient omr
            JOIN omr.message AS om
            JOIN om.senderAddress AS sa
            WHERE sa.email = :email
        SQL);

        $query->setParameter('email', $user->getEmail());

        return $query->getResult();
    }
}
