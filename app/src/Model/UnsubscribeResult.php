<?php

namespace App\Model;

use App\Entity\UnsubscribeRequest;

/**
 * Result of an unsubscription from a mailing list.
 */
class UnsubscribeResult
{
    public function __construct(
        public readonly UnsubscribeRequest $request,
        /**
         * URL the user must be redirected to, in order to complete the
         * unsubscription (HTTPS or mailto link). Null for one-click
         * unsubscriptions, which are performed by AgentJ itself.
         */
        public readonly ?string $redirectUrl = null,
    ) {
    }
}
