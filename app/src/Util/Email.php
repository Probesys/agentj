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
     * Extract the domain from an email address
     */
    public static function extractDomain(string $email): ?string
    {
        $parts = explode('@', $email);
        return count($parts) === 2 ? strtolower($parts[1]) : null;
    }
}
