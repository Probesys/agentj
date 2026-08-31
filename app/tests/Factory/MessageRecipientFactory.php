<?php

namespace App\Tests\Factory;

use App\Entity\MessageRecipient;
use DateTimeImmutable;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<MessageRecipient>
 */
final class MessageRecipientFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return MessageRecipient::class;
    }

    public function amavisRelease(
        ?DateTimeImmutable $amavisReleaseStartedAt = null,
        ?DateTimeImmutable $amavisReleaseEndedAt = null,
    ): self {
        $releaseStart = $amavisReleaseStartedAt ?:
            new DateTimeImmutable()->modify('-1 minutes');
        $releaseEnd = $amavisReleaseEndedAt ?:
            new DateTimeImmutable();

        return $this->with([
            'amavisReleaseStartedAt' => $releaseStart,
            'amavisReleaseEndedAt' => $releaseEnd,
        ]);
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
            'sendCaptcha' => self::faker()->randomElement([0, self::faker()->unixTime()]),
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
