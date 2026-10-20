<?php

namespace App\Tests\Factory;

use App\Entity\OutQuarantine;
use Zenstruck\Foundry\Object\Instantiator;

/**
 * @extends BaseQuarantineFactory<OutQuarantine>
 */
final class OutQuarantineFactory extends BaseQuarantineFactory
{
    #[\Override]
    public static function class(): string
    {
        return OutQuarantine::class;
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
