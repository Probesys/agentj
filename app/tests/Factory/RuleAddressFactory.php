<?php

namespace App\Tests\Factory;

use App\Entity\RuleAddress;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<RuleAddress>
 */
final class RuleAddressFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return RuleAddress::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'email' => '@.',
            'priority' => 0,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(RuleAddress $ruleAddress): void {})
        ;
    }
}
