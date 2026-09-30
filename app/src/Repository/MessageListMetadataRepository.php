<?php

namespace App\Repository;

use App\Entity\MessageListMetadata;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<MessageListMetadata>
 */
class MessageListMetadataRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageListMetadata::class);
    }

}
