<?php

namespace App\Tests\Factory;

use App\Entity\Policy;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentObjectFactory<Policy>
 */
final class PolicyFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Policy::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'policy_name' => self::faker()->word(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Policy $policy): void {})
        ;
    }
}
