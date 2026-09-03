<?php

namespace App\Util;

class Url
{
    /**
     * Return the reverse form of a domain name (in lower case).
     *
     * For instance, foo.example.com would be returned as com.example.foo.
     * And com.example.foo would be returned as foo.example.com.
     */
    public static function reverseDomainName(string $domainName): string
    {
        $domainParts = explode('.', $domainName);
        $reversed = implode('.', array_reverse($domainParts));
        return mb_strtolower($reversed);
    }
}
