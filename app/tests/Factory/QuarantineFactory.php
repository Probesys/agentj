<?php

namespace App\Tests\Factory;

use App\Entity\Quarantine;
use DateTime;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Quarantine>
 */
final class QuarantineFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Quarantine::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::withoutConstructor()->alwaysForce(),
        );
    }

    /**
     * @param array<mixed> $attributes
     */
    public static function generateMailText(string $mailId, array $attributes = []): string
    {
        $date = $attributes['date'] ?? new DateTime();
        $body = $attributes['body'] ?? '';

        $headers = <<<TEXT
                Message-ID: {$mailId}\r
                Subject: {$attributes['subject']}\r
                From: <{$attributes['from']}>\r
                To: support@example.com\r
                Date: {$date->format(DATE_RFC1123)}\r
                Content-Type: text/html\r
                TEXT;

        if (isset($attributes['to'])) {
            $toString = implode(', ', $attributes['to']);
            $headers .= "\nTo: {$toString}\r";
        } else {
            $headers .= "\nTo: support@example.com\r";
        }

        $attributesHeaders = $attributes['headers'] ?? [];
        foreach ($attributesHeaders as $name => $value) {
            $headers .= "\n{$name}: {$value}\r";
        }

        return "{$headers}\n\r\n\r{$body}";
    }
}
