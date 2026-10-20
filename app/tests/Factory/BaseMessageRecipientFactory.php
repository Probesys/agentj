<?php

namespace App\Tests\Factory;

use App\Entity\BaseMessageRecipient;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @template T of BaseMessageRecipient
 * @extends PersistentObjectFactory<T>
 */
abstract class BaseMessageRecipientFactory extends PersistentObjectFactory
{
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
            'sendCaptcha' => self::faker()->randomElement([0, self::faker()->unixTime()]),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::withConstructor()->alwaysForce(
                'partitionTag',
                'mailId',
                'rseqnum',
                'address'
            ),
        );
    }
}
