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

    /**
     * @param array<string>|null $attributes
     */
    public function amavisRelease(?array $attributes = []): self
    {
        $releaseStart = $attributes['amavisReleaseStartedAt'] ?:
            new DateTimeImmutable()->modify('-1 minutes');
        $releaseEnd = $attributes['amavisReleaseEndedAt'] ?:
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
            'sendCaptcha' => self::faker()->randomElement([0, 1]),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::withoutConstructor()->alwaysForce(),
        );
    }
}
