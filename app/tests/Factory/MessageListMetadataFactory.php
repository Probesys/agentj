<?php

namespace App\Tests\Factory;

use App\Entity\Message;
use App\Entity\MessageListMetadata;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * The rows of message_list_metadata are inserted by Amavis in production.
 *
 * @extends PersistentObjectFactory<MessageListMetadata>
 */
final class MessageListMetadataFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return MessageListMetadata::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'listId' => null,
            'listUnsubscribe' => null,
            'listUnsubscribePost' => null,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::withoutConstructor()->alwaysForce(),
        );
    }

    public function forMessage(Message $message): self
    {
        return $this->with([
            'message' => $message,
            'partitionTag' => $message->getPartitionTag(),
            'mailId' => $message->getMailId(),
        ]);
    }
}
