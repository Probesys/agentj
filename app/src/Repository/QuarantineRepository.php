<?php

namespace App\Repository;

use App\Amavis\SpamStatus;
use App\Entity\Message;
use App\Entity\Quarantine;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Quarantine>
 */
class QuarantineRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quarantine::class);
    }

    /**
     * Return the SpamAssassin results from the X-Spam-Status header of the
     * quarantined message, or null if not available.
     */
    public function findSpamStatus(Message $message): ?SpamStatus
    {
        // Amavis adds its headers at the top of the message, so they are
        // always in the first chunk: avoid loading the whole message.
        $firstChunk = $this->findOneBy([
            'partitionTag' => $message->getPartitionTag(),
            'mailId' => $message->getMailId(),
        ], ['chunkInd' => 'ASC']);

        if ($firstChunk === null) {
            return null;
        }

        return SpamStatus::fromRawHeaders($firstChunk->getMailText() ?? '');
    }
}
