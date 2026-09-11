<?php

namespace App\Tests\Util;

use App\Util\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testNormalizeEmailAddress(): void
    {
        self::assertSame('sender@example.org', Email::normalize('Sender@Example.ORG'));
    }

    public function testAddressLookupsAreNormalized(): void
    {
        self::assertSame([
            'user@sub.example.com',
            '@sub.example.com',
            '@.sub.example.com',
            '@.example.com',
            '@.com',
            '@.',
        ], Email::getAddressLookups('User@Sub.Example.COM'));
    }
}
