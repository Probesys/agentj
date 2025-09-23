<?php

namespace App\Util;

class Email
{
    /**
     * Extract the domain from an email address
     */
    public static function extractDomain(string $email): ?string
    {
        $parts = explode('@', $email);
        return count($parts) === 2 ? strtolower($parts[1]) : null;
    }
}
