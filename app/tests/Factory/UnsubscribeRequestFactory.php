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
            'requestedBy' => null,
            'createdAt' => new \DateTimeImmutable(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // Without constructor, so that createdAt can be set in the tests
        return $this->instantiateWith(
            Instantiator::withoutConstructor()->alwaysForce(),
        );
    }

    /**
     * Make the request for the address and the message of the message
     * recipient.
     */
    public function forMessageRecipient(MessageRecipient $messageRecipient): self
    {
        return $this->with([
            'address' => $messageRecipient->getAddress(),
            'partitionTag' => $messageRecipient->getPartitionTag(),
            'mailId' => $messageRecipient->getMailId(),
            'rseqnum' => $messageRecipient->getRseqnum(),
        ]);
    }
}
