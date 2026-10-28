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

        return $this->instantiateWith(
            Instantiator::withConstructor()->alwaysForce('createdAt'),
        );
    }

    public function forMessageRecipient(MessageRecipient $messageRecipient): self
    {
        return $this->with(['messageRecipient' => $messageRecipient]);
    }
}
