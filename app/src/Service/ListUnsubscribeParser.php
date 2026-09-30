<?php

namespace App\Service;

use App\Model\ListUnsubscribe;

/**
 * Extract the unsubscribe information from the List-Unsubscribe and
 * List-Unsubscribe-Post headers of a message.
 */
class ListUnsubscribeParser
{
    public function parse(?string $listUnsubscribe, ?string $listUnsubscribePost = null): ListUnsubscribe
    {
        $httpsUrl = null;
        $mailto = null;

        foreach ($this->extractUris($listUnsubscribe) as $uri) {
            if ($httpsUrl === null && $this->isHttpsUrl($uri)) {
                $httpsUrl = $uri;
            } elseif ($mailto === null && $this->isMailto($uri)) {
                $mailto = $uri;
            }
        }

        // RFC 8058: one-click requires both the "List-Unsubscribe=One-Click"
        // POST header and an HTTPS URI in List-Unsubscribe.
        $oneClick = $httpsUrl !== null && $this->isOneClick($listUnsubscribePost);

        return new ListUnsubscribe(
            httpsUrl: $httpsUrl,
            mailto: $mailto,
            oneClick: $oneClick,
        );
    }

    /**
     * Return the URIs of a List-Unsubscribe header, in the order of the
     * header (RFC 2369: "<uri>, <uri>").
     *
     * @return string[]
     */
    private function extractUris(?string $header): array
    {
        if ($header === null || !preg_match_all('/<([^<>]*)>/', $header, $matches)) {
            return [];
        }

        $uris = array_map(
            // Whitespaces inside the brackets are ignored (RFC 2369), they can
            // come from an URI folded on several lines.
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

        $scheme = parse_url($uri, PHP_URL_SCHEME);
        $host = parse_url($uri, PHP_URL_HOST);

        return is_string($scheme) && strtolower($scheme) === 'https'
            && is_string($host) && $host !== '';
    }

    private function isMailto(string $uri): bool
    {
        if (stripos($uri, 'mailto:') !== 0) {
            return false;
        }

        $address = substr($uri, strlen('mailto:'));
        $address = explode('?', $address, 2)[0];
        $address = rawurldecode($address);

        return filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isOneClick(?string $header): bool
    {
        if ($header === null) {
            return false;
        }

        return strcasecmp(trim($header), 'List-Unsubscribe=One-Click') === 0;
    }
}
