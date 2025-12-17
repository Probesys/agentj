<?php

namespace App\Util;

class Email
{
    /**
     * Return whether the email address is valid or not
     */
    public static function validate(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Return a list of emails contained in a string.
     *
     * @return string[]
     */
    public static function extractEmailsFromText(string $text): array
    {
        $terms = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $emails = [];
        foreach ($terms as $term) {
            if (self::validate($term)) {
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
}
