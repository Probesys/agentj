<?php

namespace App\Repository;

use App\Entity\OutMsg;
use Doctrine\DBAL;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseMessageRepository<OutMsg>
 */
class OutMsgRepository extends BaseMessageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutMsg::class);
    }

    /**
     * Delete outgoing messages older than the given date and return the number of deleted rows.
     */
    public function truncateOlder(\DateTimeInterface $date): int
    {
        $entityManager = $this->getEntityManager();

        $deleteQuery = $entityManager->createQuery(<<<SQL
            DELETE App\Entity\OutMsg m
            WHERE m.timeNum < :date
        SQL);

        $deleteQuery->setParameter('date', $date->getTimestamp());

        return $deleteQuery->execute();
    }
}
