<?php

namespace App\Util;

class Domain
{
    /**
     * Normalize a domain name by trimming whitespaces and lowering the case
     */
    public static function normalize(string $email): string
    {
        return mb_strtolower(mb_trim($email));
    }
}
