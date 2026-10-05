<?php

namespace App\Message;

final class SendOneClickUnsubscribe
{
    public function __construct(
        public readonly int $unsubscribeRequestId,
        public readonly string $url,
    ) {
    }
}
