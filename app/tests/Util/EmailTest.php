<?php

namespace App\Tests\Util;

use App\Util\Email;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('fromAddressProvider')]
    public function testParse(string $fromAddr, string $expected): void
    {
        self::assertEquals($expected, Email::parse($fromAddr));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function fromAddressProvider(): array
    {
        return [
            'john@example.com' => [
                'john@example.com',
                'john@example.com',
            ],
            'John Doe <john@example.com>' => [
                'John Doe <john@example.com>',
                'john@example.com',
            ],
            'test(comment1)(comment2)@example.com' => [
                'test(comment1)(comment2)@example.com',
                'test@example.com',
            ],
            'test@example.com (comment with (nested) parents)' => [
                'test@example.com (comment with (nested) parents)',
                'test@example.com',
            ],
            'John Doe' => [
                'John Doe',
                '',
            ],
            'empty string' => [
                '',
                '',
            ],
        ];
    }
}
