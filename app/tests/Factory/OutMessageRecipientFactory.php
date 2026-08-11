<?php

namespace App\Tests\Factory;

use App\Entity\MessageRecipient;
use App\Entity\OutMessageRecipient;
use DateTimeImmutable;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<OutMessageRecipient>
 */
final class OutMessageRecipientFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return OutMessageRecipient::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'content' => self::faker()->randomElement([
                'V','B','U','S','Y','M','H','O','T','C',
            ]),
            'ds' => self::faker()->randomElement([
                'P','R','B','D','T',
            ]),
            'isLocal' => self::faker()->randomElement([
                ' ', 'Y', 'N',
            ]),
            'rs' => ' ',
            'sendCaptcha' => self::faker()->randomElement([0, 1]),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::withConstructor()->alwaysForce(),
        );
    }
}
