<?php

namespace App\Tests\Factory;

use App\Entity\DomainKey;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DomainKey>
 */
final class DomainKeyFactory extends PersistentObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    public static function class(): string
    {
        return DomainKey::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'domain_name' => self::faker()->text(255),
            'selector' => self::faker()->boolean(),
            'private_key' => self::faker()->text(64),
            'public_key' => self::faker()->text(32),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Domain $domain): void {})
            ;
    }
}
