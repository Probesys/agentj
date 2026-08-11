<?php

namespace App\Tests\Factory;

use App\Entity\OutMessage;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<OutMessage>
 */
final class OutMessageFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return OutMessage::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'partitionTag' => 0,
            'mailId' => self::faker()->sha1(),
            'secretId' => self::faker()->sha1(),
            'amId' => self::faker()->text(20),
            'host' => self::faker()->text(255),
            'quarType' => 'Q',
            'quarLoc' => self::faker()->sha1(),
            'sendCaptcha' => self::faker()->unixTime('now'),
            'size' => self::faker()->randomNumber(),
            'timeIso' => self::faker()
                ->dateTime('now', 'UTC')
                ->format('Ymd\THis\Z'),
            'timeNum' => self::faker()->unixTime('now'),
            'validateCaptcha' => self::faker()->randomNumber(),
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
