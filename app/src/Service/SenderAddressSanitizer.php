<?php

namespace App\Service;

class SenderAddressSanitizer
{
    public function sanitize(string $value): ?string
    {
        $value = strtolower(trim($value));

        $email = filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE);
        if ($email !== false) {
            return $email;
        }

        // Domains are stored with an @ prefix, regardless of the submitted format.
        if (str_starts_with($value, '@')) {
            $value = substr($value, 1);
        }

        $domain = filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
        if ($domain !== false) {
            return '@' . $domain;
        }

        return null;
    }
}
