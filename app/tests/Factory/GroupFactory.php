<?php

namespace App\Tests\Factory;

use App\Entity\Group;
use App\Entity\SenderRule;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Group>
 */
final class GroupFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Group::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'active' => true,
            'domain' => DomainFactory::new(),
            'priority' => SenderRule::PRIORITY_USER,
            'name' => self::faker()->text(25),
            'slug' => self::faker()->text(10),
            'wb' => self::faker()->randomElement([
                ' ',
                'W',
                'B',
            ]),
            'overrideUser' => false,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Group $groups): void {})
        ;
    }
}
