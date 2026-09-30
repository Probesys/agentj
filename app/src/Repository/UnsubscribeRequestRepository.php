<?php

namespace App\Repository;

use App\Entity\Address;
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
     * Return the last unsubscribe request made by the address for the list.
     */
    public function findLastForList(Address $address, string $listId): ?UnsubscribeRequest
    {
        return $this->findOneBy(
            ['address' => $address, 'listId' => $listId],
            ['createdAt' => 'DESC', 'id' => 'DESC'],
        );
    }

    /**
     * Return the last unsubscribe request made from the message recipient.
     */
    public function findLastForMessageRecipient(MessageRecipient $messageRecipient): ?UnsubscribeRequest
    {
        return $this->findOneBy(
            [
                'partitionTag' => $messageRecipient->getPartitionTag(),
                'mailId' => $messageRecipient->getMailId(),
                'rseqnum' => $messageRecipient->getRseqnum(),
            ],
            ['createdAt' => 'DESC', 'id' => 'DESC'],
        );
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
