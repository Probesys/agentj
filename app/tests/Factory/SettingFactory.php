<?php

namespace App\Tests\Factory;

use App\Entity\Setting;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Setting>
 */
final class SettingFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Setting::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'context' => self::faker()->text(50),
            'name' => self::faker()->text(50),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Setting $setting): void {})
        ;
    }
}
