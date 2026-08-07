<?php

namespace App\Tests\Factory;

use App\Entity\Address;
use App\Util\Url;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Address>
 */
final class AddressFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Address::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'domain' => Url::reverseDomainName(self::faker()->domainName()),
            'email' => null,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Address $address): void {})
        ;
    }
}
