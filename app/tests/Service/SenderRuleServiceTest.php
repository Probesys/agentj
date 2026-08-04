<?php

namespace App\Tests\Service;

use App\Entity\SenderRule;
use App\Service\SenderRuleService;
use App\Tests\FactoryHelper;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SenderRuleServiceTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use FactoryHelper;

    public function testImportFileUsesSharedRuleCreation(): void
    {
        self::bootKernel();
        $domain = DomainFactory::new()->create();
        $domainUser = UserFactory::find(['email' => '@' . $domain]);
        $path = tempnam(sys_get_temp_dir(), 'sender-rules-test-');
        self::assertIsString($path);
        file_put_contents($path, "sender@example.org\nexample.net\ninvalid value\nsender@example.org\n");

        try {
            $service = static::getContainer()->get(SenderRuleService::class);
            $service->importFile($path, $domain, 'accept');
        } finally {
            unlink($path);
        }

        foreach (['sender@example.org' => 6, '@example.net' => 5] as $address => $priority) {
            RuleAddressFactory::assert()->exists([
                'email' => $address,
                'priority' => $priority,
            ]);
            $ruleAddress = RuleAddressFactory::find(['email' => $address]);
            SenderRuleFactory::assert()->exists([
                'user' => $domainUser,
                'senderRuleAddress' => $ruleAddress,
                'wb' => ' ',
                'type' => SenderRule::TYPE_IMPORT,
                'priority' => SenderRule::PRIORITY_DOMAIN,
            ]);
        }

        // At domain creation, it needs to have a root '@.' RuleAddress (shared accross all domains).
        // Creating a domain also creates a SenderRule using this RuleAddress.
        self::assertSame(3, RuleAddressFactory::count());
        self::assertSame(3, SenderRuleFactory::count());
    }

    public function testCreateOrUpdateForUserAndAliasesDoesNotReplaceGroupRule(): void
    {
        self::bootKernel();
        $user = UserFactory::new()->user()->create();
        $ruleAddress = RuleAddressFactory::createOne([
            'email' => 'sender@example.org',
            'priority' => 6,
        ]);
        $groupRule = SenderRuleFactory::createOne([
            'user' => $user,
            'senderRuleAddress' => $ruleAddress,
            'wb' => 'block',
            'type' => SenderRule::TYPE_GROUP,
            'priority' => SenderRule::PRIORITY_GROUP_OVERRIDE,
        ]);

        $service = static::getContainer()->get(SenderRuleService::class);
        self::assertTrue($service->createOrUpdateForUserAndAliases(
            'sender@example.org',
            $user,
            'accept',
            SenderRule::TYPE_USER,
        ));

        SenderRuleFactory::assert()->exists([
            'user' => $user,
            'senderRuleAddress' => $ruleAddress,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => ' ',
            'type' => SenderRule::TYPE_USER,
        ]);
        $this->refresh($groupRule);
        self::assertSame('block', $groupRule->getWbRule());
        self::assertSame(SenderRule::TYPE_GROUP, $groupRule->getType());
        self::assertSame(SenderRule::PRIORITY_GROUP_OVERRIDE, $groupRule->getPriority());
    }

    public function testCreateOrUpdateForUserAndAliasesCreatesRulesForMainUserAndAliases(): void
    {
        self::bootKernel();
        $domain = DomainFactory::createOne();
        $mainUser = UserFactory::new()->user($domain)->create();
        $alias = UserFactory::new()->alias($mainUser)->create();
        self::assertSame($mainUser, $alias->getMainUser());

        $service = static::getContainer()->get(SenderRuleService::class);
        self::assertTrue($service->createOrUpdateForUserAndAliases(
            'sender@example.org',
            $alias,
            'block',
            SenderRule::TYPE_USER,
        ));

        $ruleAddress = RuleAddressFactory::find(['email' => 'sender@example.org']);

        foreach ([$mainUser, $alias] as $recipient) {
            SenderRuleFactory::assert()->exists([
                'user' => $recipient,
                'senderRuleAddress' => $ruleAddress,
                'priority' => SenderRule::PRIORITY_USER,
            ]);
        }
    }
}
