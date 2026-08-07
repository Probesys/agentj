<?php

namespace App\Tests\Factory;

use App\Entity\Group;
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
            'domain' => DomainFactory::new(),
            'name' => self::faker()->text(255),
            'slug' => self::faker()->text(128),
            'wb' => self::faker()->text(10),
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
