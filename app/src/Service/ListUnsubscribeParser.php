<?php

namespace App\Service;

use App\Model\UnsubscribeMethods;

/**
 * Extract the unsubscribe information from the List-Unsubscribe and
 * List-Unsubscribe-Post headers of a message.
 */
class ListUnsubscribeParser
{
    public function parse(?string $listUnsubscribe, ?string $listUnsubscribePost = null): UnsubscribeMethods
    {
        $httpsUrl = null;
        $mailto = null;
        $mailtoAddress = null;

        foreach ($this->extractUris($listUnsubscribe) as $uri) {
            if ($httpsUrl === null && $this->isHttpsUrl($uri)) {
                $httpsUrl = $uri;
            } elseif ($mailto === null) {
                $mailtoAddress = $this->extractMailtoAddress($uri);
                $mailto = $mailtoAddress !== null ? $uri : null;
            }
        }

        $oneClick = $httpsUrl !== null && $this->isOneClick($listUnsubscribePost);

        return new UnsubscribeMethods(
            httpsUrl: $httpsUrl,
            mailto: $mailto,
            mailtoAddress: $mailtoAddress,
            oneClick: $oneClick,
        );
    }

    /**
     * Return the URIs of a List-Unsubscribe header
     *
     * @return string[]
     */
    private function extractUris(?string $header): array
    {
        if ($header === null || !preg_match_all('/<([^<>]*)>/', $header, $matches)) {
            return [];
        }

        $uris = array_map(
            //Remove whitespace from the URI.
            fn (string $uri): string => (string) preg_replace('/\s+/', '', $uri),
            $matches[1],
        );

        return array_values(array_filter($uris, fn (string $uri): bool => $uri !== ''));
    }

    private function isHttpsUrl(string $uri): bool
    {
        if (filter_var($uri, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return strtolower((string) parse_url($uri, PHP_URL_SCHEME)) === 'https';
    }

    /**
     * Return the email address of a mailto URI (decoded and without its
     * parameters), or null if the URI is not a mailto URI with a valid address.
     */
    private function extractMailtoAddress(string $uri): ?string
    {
        if (stripos($uri, 'mailto:') !== 0) {
            return null;
        }

        $address = substr($uri, strlen('mailto:'));
        $address = explode('?', $address, 2)[0];
        $address = rawurldecode($address);

        return filter_var($address, FILTER_VALIDATE_EMAIL) !== false ? $address : null;
    }

    private function isOneClick(?string $header): bool
    {
        if ($header === null) {
            return false;
        }

        return strtolower(trim($header)) === 'list-unsubscribe=one-click';
    }
}
