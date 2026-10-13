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
        $mailtoAddresses = [];

        foreach ($this->extractUris($listUnsubscribe) as $uri) {
            if ($httpsUrl === null && $this->isHttpsUrl($uri)) {
                $httpsUrl = $uri;
            } elseif ($mailto === null) {
                $mailtoAddresses = $this->extractMailtoAddresses($uri);
                $mailto = $mailtoAddresses !== [] ? $uri : null;
            }
        }

        $isOneClick = $httpsUrl !== null && $this->isOneClickPostHeader($listUnsubscribePost);

        return new UnsubscribeMethods(
            httpsUrl: $httpsUrl,
            mailto: $mailto,
            mailtoAddresses: $mailtoAddresses,
            isOneClick: $isOneClick,
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
     * Return the valid email addresses of a mailto URI (decoded), or an empty
     * array if the URI is not a mailto URI.
     *
     * The addresses can be in the path and in the "to" fields, separated by
     * commas (e.g. "mailto:a@example.com,b@example.com?to=c@example.com").
     *
     * @see https://www.rfc-editor.org/rfc/rfc6068
     *
     * @return string[]
     */
    private function extractMailtoAddresses(string $uri): array
    {
        if (stripos($uri, 'mailto:') !== 0) {
            return [];
        }

        $uri = substr($uri, strlen('mailto:'));
        [$path, $query] = array_pad(explode('?', $uri, 2), 2, '');

        // The addresses are split before being decoded, as an encoded comma
        // is part of an address.
        $encodedAddresses = explode(',', $path);

        foreach (explode('&', $query) as $field) {
            [$name, $value] = array_pad(explode('=', $field, 2), 2, '');
            if (strtolower(rawurldecode($name)) === 'to') {
                $encodedAddresses = array_merge($encodedAddresses, explode(',', $value));
            }
        }

        $addresses = [];
        foreach ($encodedAddresses as $encodedAddress) {
            $address = trim(rawurldecode($encodedAddress));
            if (filter_var($address, FILTER_VALIDATE_EMAIL) !== false) {
                $addresses[] = $address;
            }
        }

        return array_values(array_unique($addresses));
    }

    private function isOneClickPostHeader(?string $header): bool
    {
        if ($header === null) {
            return false;
        }

        return strtolower(trim($header)) === 'list-unsubscribe=one-click';
    }
}
