<?php

namespace App\Tests\Migration;

use App\Entity\SenderRule;
use App\Entity\User;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MigrationHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DomainSenderRulePriorityMigrationTest extends KernelTestCase
{
    use Factories;
    use MigrationHelper;
    use ResetDatabase;

    public function testItMovesDomainUserRulesToTheDomainPriority(): void
    {
        $domain = DomainFactory::createOne();
        $domainUser = $this->domainUser($domain->getDomain());
        $senderRuleAddress = RuleAddressFactory::createOne(['email' => 'sender@example.org', 'priority' => 6]);
        SenderRuleFactory::createOne([
            'user' => $domainUser,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => 'B',
            'priority' => SenderRule::PRIORITY_USER,
        ]);

        self::runMigration('Version20261019210121');

        $senderRules = SenderRuleFactory::findBy([
            'user' => $domainUser,
            'senderRuleAddress' => $senderRuleAddress,
        ]);
        self::assertCount(1, $senderRules);
        self::assertSame('B', $senderRules[0]->getWb());
        self::assertSame(SenderRule::PRIORITY_DOMAIN, $senderRules[0]->getPriority());
    }

    public function testItKeepsTheRuleCurrentlyAppliedOnConflict(): void
    {
        $domain = DomainFactory::createOne();
        $domainUser = $this->domainUser($domain->getDomain());
        $senderRuleAddress = RuleAddressFactory::createOne(['email' => 'sender@example.org', 'priority' => 6]);
        SenderRuleFactory::createOne([
            'user' => $domainUser,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => 'W',
            'priority' => SenderRule::PRIORITY_DOMAIN,
        ]);
        SenderRuleFactory::createOne([
            'user' => $domainUser,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => 'B',
            'priority' => SenderRule::PRIORITY_USER,
        ]);

        self::runMigration('Version20261019210121');

        $senderRules = SenderRuleFactory::findBy([
            'user' => $domainUser,
            'senderRuleAddress' => $senderRuleAddress,
        ]);
        self::assertCount(1, $senderRules);
        self::assertSame('B', $senderRules[0]->getWb());
        self::assertSame(SenderRule::PRIORITY_DOMAIN, $senderRules[0]->getPriority());
    }

    public function testItDoesNotChangeRulesOfStandardUsers(): void
    {
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $senderRuleAddress = RuleAddressFactory::createOne(['email' => 'sender@example.org', 'priority' => 6]);
        SenderRuleFactory::createOne([
            'user' => $user,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => 'W',
            'priority' => SenderRule::PRIORITY_USER,
        ]);

        self::runMigration('Version20261019210121');

        $senderRules = SenderRuleFactory::findBy([
            'user' => $user,
            'senderRuleAddress' => $senderRuleAddress,
        ]);
        self::assertCount(1, $senderRules);
        self::assertSame('W', $senderRules[0]->getWb());
        self::assertSame(SenderRule::PRIORITY_USER, $senderRules[0]->getPriority());
    }

    private function domainUser(string $domainName): User
    {
        $domainUser = UserFactory::repository()->findDomainUser($domainName);
        self::assertInstanceOf(User::class, $domainUser);

        return $domainUser;
    }
}
