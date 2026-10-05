<?php

namespace App\Tests\Factory;

use App\Entity\MessageRecipient;
use App\Entity\UnsubscribeRequest;
use App\Model\UnsubscribeMethods;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<UnsubscribeRequest>
 */
final class UnsubscribeRequestFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return UnsubscribeRequest::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'listId' => null,
            'method' => UnsubscribeMethods::METHOD_ONE_CLICK,
            'status' => UnsubscribeRequest::STATUS_SENT,
            'createdAt' => new \DateTimeImmutable(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(UnsubscribeRequest $unsubscribeRequest): void {})
            ;
    }

    public function forMessageRecipient(MessageRecipient $messageRecipient): self
    {
        return $this->with([
            'partitionTag' => $messageRecipient->getPartitionTag(),
            'mailId' => $messageRecipient->getMailId(),
            'rseqnum' => $messageRecipient->getRseqnum(),
            'address' => $messageRecipient->getAddress(),
        ]);
    }
}
