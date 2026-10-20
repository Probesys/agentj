<?php

namespace App\Tests\Factory;

use App\Entity\Office365Connector;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Office365Connector>
 */
final class Office365ConnectorFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Office365Connector::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'domain' => DomainFactory::new(),
            'name' => self::faker()->text(50),
            'type' => 'Office365',
            'tenant' => self::faker()->sha1(),
            'clientId' => self::faker()->sha1(),
            'clientSecret' => self::faker()->sha1(),
            'synchronizeGroup' => 1,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Office365Connector $office365Connector): void {})
        ;
    }

    public function neverExecuted(): self
    {
        return $this->with([
            'importStartedAt' => null,
            'lastSuccessResult' => [],
            'lastSuccessAt' => null,
            'lastErrorResult' => '',
            'lastErrorAt' => null,
        ]);
    }
}
