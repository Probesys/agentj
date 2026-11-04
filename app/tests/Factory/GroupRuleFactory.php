<?php

namespace App\Tests\Factory;

use App\Entity\GroupRule;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<GroupRule>
 */
final class GroupRuleFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return GroupRule::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'group' => null, // TODO add App\Entity\Group type manually
            'ruleAddress' => null, // TODO add App\Entity\RuleAddress type manually
            'wb' => self::faker()->randomElement([
                'accept',
                'block',
                'allow',
                'enabled',
                'none',
            ]),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(GroupRule $groupRule): void {})
        ;
    }
}
