<?php

namespace App\Tests\Repository;

use App\Entity\SenderRule;
use App\Repository\SenderRuleRepository;
use App\Repository\UserRepository;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SenderRuleRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testItMatchesSenderRulesRegardlessOfMessageCase(): void
    {
        self::bootKernel();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipientAddress = AddressFactory::createOne([
            'email' => $recipient->getEmail(),
        ]);
        $ruleAddress = RuleAddressFactory::createOne([
            'email' => 'sender@example.org',
            'priority' => 6,
        ]);
        SenderRuleFactory::createOne([
            'user' => $recipient,
            'senderRuleAddress' => $ruleAddress,
            'wb' => 'accept',
            'priority' => SenderRule::PRIORITY_USER,
        ]);

        $repository = static::getContainer()->get(SenderRuleRepository::class);

        self::assertTrue($repository->isSenderAuthorizedByRecipient('Sender@Example.ORG', $recipientAddress));
    }

    public function testIsSenderAuthorizedByRecipientUsesTheSameRulePriorityAsTheRegularLookup(): void
    {
        self::bootKernel();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipientAddress = AddressFactory::createOne([
            'email' => $recipient->getEmail(),
        ]);
        $domainUser = static::getContainer()->get(UserRepository::class)->findDomainUser($domain->getDomain());
        self::assertNotNull($domainUser);
        $sender = 'sender@example.org';
        $senderDomain = RuleAddressFactory::createOne(['email' => '@example.org', 'priority' => 5]);
        $senderAddress = RuleAddressFactory::createOne(['email' => $sender, 'priority' => 6]);

        SenderRuleFactory::createOne([
            'user' => $domainUser,
            'senderRuleAddress' => $senderDomain,
            'wb' => 'block',
            'priority' => SenderRule::PRIORITY_DOMAIN,
        ]);
        SenderRuleFactory::createOne([
            'user' => $recipient,
            'senderRuleAddress' => $senderDomain,
            'wb' => 'accept',
            'priority' => SenderRule::PRIORITY_GROUP,
        ]);
        SenderRuleFactory::createOne([
            'user' => $recipient,
            'senderRuleAddress' => $senderAddress,
            'wb' => 'block',
            'priority' => SenderRule::PRIORITY_USER,
        ]);

        $repository = static::getContainer()->get(SenderRuleRepository::class);
        $rules = $repository->findBySenderEmailAndRecipient($sender, $recipientAddress);
        self::assertNotEmpty($rules);
        self::assertSame(
            $rules[0]->isWbRuleAuthorized(),
            $repository->isSenderAuthorizedByRecipient($sender, $recipientAddress),
        );
        self::assertFalse($repository->isSenderAuthorizedByRecipient($sender, $recipientAddress));

        self::assertTrue($repository->isSenderAuthorizedByRecipient('someone@example.org', $recipientAddress));
    }
}
