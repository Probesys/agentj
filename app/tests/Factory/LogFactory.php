<?php

namespace App\Tests\Factory;

use App\Entity\Log;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Log>
 */
final class LogFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Log::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Log $log): void {})
        ;
    }
}
