<?php

namespace App\Tests\Service;

use App\Service\SenderAddressSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SenderAddressSanitizerTest extends TestCase
{
    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function provideAddresses(): iterable
    {
        yield 'email' => ['sender@example.org', 'sender@example.org'];
        yield 'trimmed email' => ['  sender@example.org  ', 'sender@example.org'];
        yield 'uppercase email' => ['Sender@Example.ORG', 'sender@example.org'];
        yield 'domain' => ['example.org', '@example.org'];
        yield 'prefixed domain' => ['@example.org', '@example.org'];
        yield 'uppercase domain' => ['Example.ORG', '@example.org'];
        yield 'empty value' => ['', null];
        yield 'invalid value' => ['not an address', null];
    }

    #[DataProvider('provideAddresses')]
    public function testSanitize(string $value, ?string $expected): void
    {
        $sanitizer = new SenderAddressSanitizer();

        self::assertSame($expected, $sanitizer->sanitize($value));
    }
}
