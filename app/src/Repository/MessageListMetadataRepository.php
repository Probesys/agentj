<?php

namespace App\Repository;

use App\Entity\MessageListMetadata;
use App\Entity\MessageRecipient;
use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Criteria;
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

    public function findByMessageRecipient(MessageRecipient $messageRecipient): ?MessageListMetadata
    {
        return $this->find([
            'partitionTag' => $messageRecipient->getPartitionTag(),
            'mailId' => $messageRecipient->getMailId(),
        ]);
    }

    /**
     * @param MessageRecipient[] $messageRecipients
     *
     * @return AbstractLazyCollection<int, MessageListMetadata>
     */
    public function findByMessageRecipients(array $messageRecipients): AbstractLazyCollection
    {
        $criteria = new Criteria();
        foreach ($messageRecipients as $messageRecipient) {
            $expressionBuilder = Criteria::expr();
            $expression = $expressionBuilder->andX(
                $expressionBuilder->eq('partitionTag', $messageRecipient->getPartitionTag()),
                $expressionBuilder->eq('mailId', $messageRecipient->getMailId()),
            );
            $criteria->orWhere($expression);
        }

        return $this->matching($criteria);
    }
}
