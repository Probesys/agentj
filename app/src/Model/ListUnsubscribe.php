<?php

namespace App\Model;

/**
 * Unsubscribe information of a message sent through a mailing list.
 *
 * @see https://www.rfc-editor.org/rfc/rfc2369 (List-Unsubscribe)
 * @see https://www.rfc-editor.org/rfc/rfc8058 (List-Unsubscribe-Post, one-click)
 */
class ListUnsubscribe
{
    public const METHOD_ONE_CLICK = 'one-click';
    public const METHOD_HTTPS = 'https';
    public const METHOD_MAILTO = 'mailto';

    public function __construct(
        public readonly ?string $httpsUrl = null,
        public readonly ?string $mailto = null,
        public readonly bool $oneClick = false,
    ) {
    }

    /**
     * Return the preferred unsubscribe method, or null if the message cannot
     * be unsubscribed from.
     *
     * One-click (RFC 8058) is preferred because AgentJ can perform it by
     * itself, then HTTPS (the user has to complete the process on the list
     * website), then mailto.
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

    public function canUnsubscribe(): bool
    {
        return $this->getPreferredMethod() !== null;
    }
}
