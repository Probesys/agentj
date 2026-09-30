<?php

namespace App\Repository;

use App\Entity\MessageRecipient;
use App\Entity\UnsubscribeRequest;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<UnsubscribeRequest>
 */
class UnsubscribeRequestRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnsubscribeRequest::class);
    }

    /**
     * Return the last unsubscribe request related to a message recipient:
     * made for the same address and the same list, or from this message if
     * it has no List-Id.
     */
    public function findLastForMessageRecipient(
        MessageRecipient $messageRecipient,
        ?string $listId,
    ): ?UnsubscribeRequest {
        $queryBuilder = $this->createQueryBuilder('ur');

        if ($listId !== null) {
            $queryBuilder
                ->where('ur.address = :address')
                ->andWhere('ur.listId = :listId')
                ->setParameter('address', $messageRecipient->getAddress())
                ->setParameter('listId', $listId);
        } else {
            $queryBuilder
                ->where('ur.partitionTag = :partitionTag')
                ->andWhere('ur.mailId = :mailId')
                ->andWhere('ur.rseqnum = :rseqnum')
                ->setParameter('partitionTag', $messageRecipient->getPartitionTag())
                ->setParameter('mailId', $messageRecipient->getMailId())
                ->setParameter('rseqnum', $messageRecipient->getRseqnum());
        }

        return $queryBuilder
            ->orderBy('ur.createdAt', 'DESC')
            ->addOrderBy('ur.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Delete the requests older than $nbDays and return the number of
     * deleted requests.
     */
    public function truncateOlder(int $nbDays): int
    {
        $date = new \DateTimeImmutable("-{$nbDays} days");

        return $this->getEntityManager()
            ->createQuery(<<<DQL
                DELETE App\Entity\UnsubscribeRequest ur
                WHERE ur.createdAt <= :date
            DQL)
            ->setParameter('date', $date)
            ->execute();
    }
}
