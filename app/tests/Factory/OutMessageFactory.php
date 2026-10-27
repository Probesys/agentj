<?php

namespace App\Tests\Factory;

use App\Entity\OutMessage;

/**
 * @extends BaseMessageFactory<OutMessage>
 */
final class OutMessageFactory extends BaseMessageFactory
{
    #[\Override]
    public static function class(): string
    {
        return OutMessage::class;
    }
}
