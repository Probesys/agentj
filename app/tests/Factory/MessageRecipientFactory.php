<?php

namespace App\Tests\Factory;

use App\Entity\MessageRecipient;
use DateTimeImmutable;

/**
 * @extends BaseMessageRecipientFactory<MessageRecipient>
 */
final class MessageRecipientFactory extends BaseMessageRecipientFactory
{
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
}
