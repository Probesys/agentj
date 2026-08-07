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
        $privateKey = openssl_pkey_new();
        if ($privateKey === false) {
            throw new \RuntimeException('Unable to generate private key');
        }
        $privateKeyPem = null;
        openssl_pkey_export($privateKey, $privateKeyPem);
        $details = openssl_pkey_get_details($privateKey);
        if ($details === false) {
            throw new \RuntimeException('Unable to get key details');
        }
        $publicKeyPem = $details['key'];

        return [
            'domain_name' => self::faker()->text(255),
            'selector' => self::faker()->boolean(),
            'private_key' => $privateKeyPem,
            'public_key' => $publicKeyPem,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(DomainKey $domainKey): void {})
            ;
    }
}
