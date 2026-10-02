<?php

namespace App\Tests\Factory;

use App\Entity\ImapConnector;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ImapConnector>
 */
final class ImapConnectorFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return ImapConnector::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'domain' => DomainFactory::new(),
            'name' => self::faker()->text(50),
            'type' => 'IMAP',
            'imapPort' => 993,
            'imapProtocol' => 'ssl',
            'ïmapNoValidateCert' => 0,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (ImapConnector $imapConnector): void {
                $domain = $imapConnector->getDomain();
                $imapConnector->setName('imap.' . $domain->getDomain());
            });
    }
}
