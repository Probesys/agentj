<?php

namespace App\Tests\Factory;

use App\Entity\Message;

/**
 * @extends BaseMessageFactory<Message>
 */
final class MessageFactory extends BaseMessageFactory
{
    #[\Override]
    public static function class(): string
    {
        return Message::class;
    }
}
