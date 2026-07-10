<?php

namespace App\Repository;

use App\Entity\OutMessageRecipient;
use App\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\OutMessage;
use App\Entity\Address;

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
            ->leftJoin(
                OutMessage::class,
                'm',
                Join::WITH,
                'm.mailId = mr.mailId AND m.partitionTag = mr.partitionTag'
            )
            ->leftJoin(Address::class, 'maddr', Join::WITH, 'maddr.id = mr.rid');

        return $queryBuilder;
    }

    /**
     * @return OutMessageRecipient[]
     */
    public function findByEmailSender(User $user): array
    {
        $query = $this->getEntityManager()->createQuery(<<<SQL
            SELECT omr
            FROM OutMessageRecipient omr
            JOIN omr.message AS om
            JOIN om.sid AS s
            WHERE s.email = :email
        SQL);

        $query->setParameter('email', $user->getEmail());

        return $query->getResult();
    }
}
