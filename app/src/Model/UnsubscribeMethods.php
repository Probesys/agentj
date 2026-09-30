<?php

namespace App\Model;

/**
 * Unsubscribe information of a message sent through a mailing list.
 *
 * @see https://www.rfc-editor.org/rfc/rfc2369 (List-Unsubscribe)
 * @see https://www.rfc-editor.org/rfc/rfc8058 (List-Unsubscribe-Post, one-click)
 */
class UnsubscribeMethods
{
    public const METHOD_ONE_CLICK = 'one-click';
    public const METHOD_HTTPS = 'https';
    public const METHOD_MAILTO = 'mailto';

    public function __construct(
        public readonly ?string $httpsUrl = null,
        public readonly ?string $mailto = null,
        // The email address of the mailto URI, decoded and without parameters
        public readonly ?string $mailtoAddress = null,
        public readonly bool $oneClick = false,
    ) {
    }

    /**
     * Return the preferred unsubscribe method: one-click (RFC 8058), then
     * HTTPS and mailto (RFC 2369).
     */
    public function getPreferredMethod(): ?string
    {
        if ($this->oneClick) {
            return self::METHOD_ONE_CLICK;
        }

        if ($this->httpsUrl !== null) {
            return self::METHOD_HTTPS;
        }

        if ($this->mailto !== null) {
            return self::METHOD_MAILTO;
        }

        return null;
    }

    public function hasMethod(): bool
    {
        return $this->getPreferredMethod() !== null;
    }
}
