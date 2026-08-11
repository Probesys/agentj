<?php

namespace App\Tests\Factory;

use App\Entity\Quarantine;
use App\Tests\MessageHelper;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Quarantine>
 */
final class QuarantineFactory extends PersistentObjectFactory
{
    use MessageHelper;

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
}
