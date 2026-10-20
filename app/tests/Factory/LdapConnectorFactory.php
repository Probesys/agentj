<?php

namespace App\Tests\Factory;

use App\Entity\LdapConnector;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<LdapConnector>
 */
final class LdapConnectorFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return LdapConnector::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'domain' => DomainFactory::new(),
            'name' => self::faker()->text(50),
            'type' => 'LDAP',
            'ldapHost' => 'ldap',
            'ldapPort' => '389',
            'ldapPassword' => self::faker()->sha1(),
            'ldapRealNameField' => 'displayName',
            'ldapEmailField' => 'mail',
            'ldapUserFilter' => '(cn=*)',
            'synchronize_group' => 0,
            'allow_anonymous_bind' => 1,
            'ldapAliasField' => 'alias',
            'ldapEncryption' => 'none',
            'ldapVersion' => 3,
            'ldapAllowConnection' => 1,
            'ldapHumanAuthenticationEnabled' => 1,
            'ldapReport' => 1,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->afterInstantiate(function (LdapConnector $ldapConnector): void {
            $domain = $ldapConnector->getDomain();
            $parts = explode('.', $domain->getDomain());
            $ldapConnector->setLdapBaseDn(
                sprintf(
                    'ou=users,dc=%s,dc=%s',
                    $parts[count($parts) - 2],
                    $parts[count($parts) - 1],
                )
            );
        });
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
