<?php

namespace App\Tests\Factory;

use App\Entity\Domain;
use App\Entity\SenderRule;
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

    public function withDomain(string $domain): self
    {
        return $this->with([
            'domain' => $domain,
            'srvSmtp' => 'smtp.' . $domain,
        ]);
    }

    #[\Override]
    protected function defaults(): array
    {
        $domain = self::faker()->unique()->domainName;
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
            ->afterPersist(function (Domain $domain) {
                $user = UserFactory::new()->create([
                    'email' => '@' . $domain->getDomain(),
                    'domain' => $domain,
                ]);

                $rootMailAddr = RuleAddressFactory::findOrCreate(['email' => '@.']);

                $senderRule = new SenderRule($user, $rootMailAddr);
                $senderRule->setWb('allow');
                $senderRule->setPriority(SenderRule::PRIORITY_USER);

                SenderRuleFactory::repository()->save($senderRule);
            })
        ;
    }
}
