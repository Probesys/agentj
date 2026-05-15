<?php

namespace App\Util;

class Url
{
    /**
     * Return the reverse form of a domain name.
     *
     * For instance, foo.example.com would be returned as com.example.foo.
     * And com.example.foo would be returned as foo.example.com.
     */
    public static function reverseDomainName(string $domainName): string
    {
        $domainParts = explode('.', $domainName);
        return implode('.', array_reverse($domainParts));
    }
}
