<?php

namespace App\Util;

class Email
{
    /**
     * Return whether the email address is valid or not
     */
    public static function validate(string $email, bool $looseMode = false): bool
    {
        if ($looseMode) {
            return str_contains($email, '@');
        } else {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        }
    }

    /**
     * Return a list of emails contained in a string.
     *
     * @return string[]
     */
    public static function extractEmailsFromText(string $text, bool $looseMode = false): array
    {
        $terms = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $emails = [];
        foreach ($terms as $term) {
            if (self::validate($term, $looseMode)) {
                $emails[] = $term;
            }
        }

        return $emails;
    }

    /**
     * Extract the domain from an email address
     */
    public static function extractDomain(string $email): ?string
    {
        $parts = explode('@', $email);
        return count($parts) === 2 ? strtolower($parts[1]) : null;
    }

    /**
     * Return a list of email address lookups.
     *
     * The list is built according to how Amavis is looking for emails when
     * white/backlisting. For instance, considering the email
     * "user@sub.example.com", the method returns the following:
     *
     * - user@sub.example.com
     * - @sub.example.com
     * - @.sub.example.com
     * - @.example.com
     * - @.com
     * - @.
     *
     * @return string[]
     */
    public static function getAddressLookups(string $email): array
    {
        if (!self::validate($email)) {
            return [];
        }

        list($localUser, $domain) = explode('@', $email, limit: 2);

        $lookup = [];
        $lookup[] = $email;

        $lookup[] = '@' . $domain;

        $domainParts = explode('.', $domain);

        while (count($domainParts) > 0) {
            $lookup[] = '@.' . implode('.', $domainParts);
            array_shift($domainParts);
        }

        $lookup[] = '@.';

        return $lookup;
    }
}
