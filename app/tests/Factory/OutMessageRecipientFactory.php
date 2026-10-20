<?php

namespace App\Tests\Factory;

use App\Entity\OutMessageRecipient;

/**
 * @extends BaseMessageRecipientFactory<OutMessageRecipient>
 */
final class OutMessageRecipientFactory extends BaseMessageRecipientFactory
{
    #[\Override]
    public static function class(): string
    {
        return OutMessageRecipient::class;
    }
}
