<?php

namespace App\Tests\Factory;

use App\Entity\Domain;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Domain>
 */
final class DomainFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Domain::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        $domain = self::faker()->domainName;
        return [
            'active' => true,
            'domain' => $domain,
            'srvSmtp' => 'smtp.' . $domain,
            'smtpPort' => 25,
            'domainKeys' => DomainKeyFactory::new(),
            'policy' => PolicyFactory::new(),
            'logo' => self::faker()->imageUrl(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (Domain $domain): void {
                $domain->setCalculatedTransport();
            })
        ;
    }
}
