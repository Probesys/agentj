<?php

namespace App\Tests\Factory;

use App\Entity\Quarantine;
use Zenstruck\Foundry\Object\Instantiator;

/**
 * @extends BaseQuarantineFactory<Quarantine>
 */
final class QuarantineFactory extends BaseQuarantineFactory
{
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
