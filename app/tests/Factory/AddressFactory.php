<?php

namespace App\Tests\Factory;

use App\Entity\Address;
use App\Util\Email;
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
            'partitionTag' => 0,
            'email' => self::faker()->unique()->email(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->beforeInstantiate(function (array $attributes): array {
            $attributes['domain'] ??= Url::reverseDomainName(Email::extractDomain($attributes['email']));

            return $attributes;
        });
    }
}
