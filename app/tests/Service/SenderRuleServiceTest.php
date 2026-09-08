<?php

namespace App\Tests\Service;

use App\Entity\RuleAddress;
use App\Entity\SenderRule;
use App\Repository\RuleAddressRepository;
use App\Repository\SenderRuleRepository;
use App\Service\SenderRuleService;
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

    public function testImportFileUsesSharedRuleCreation(): void
    {
        self::bootKernel();
        $domain = DomainFactory::new()->create();
        $domainUser = UserFactory::findBy(['email' => '@' . $domain]);
        $path = tempnam(sys_get_temp_dir(), 'sender-rules-test-');
        self::assertIsString($path);
        file_put_contents($path, "sender@example.org\nexample.net\ninvalid value\nsender@example.org\n");

        try {
            $service = static::getContainer()->get(SenderRuleService::class);
            $service->importFile($path, $domain, 'accept');
        } finally {
            unlink($path);
        }

        $ruleAddressRepository = static::getContainer()->get(RuleAddressRepository::class);
        $senderRuleRepository = static::getContainer()->get(SenderRuleRepository::class);

        foreach (['sender@example.org' => 6, '@example.net' => 5] as $address => $priority) {
            $ruleAddress = $ruleAddressRepository->findOneBy(['email' => $address]);
            self::assertNotNull($ruleAddress);
            self::assertSame($priority, $ruleAddress->getPriority());

            $senderRule = $senderRuleRepository->findOneBy([
                'user' => $domainUser,
                'senderRuleAddress' => $ruleAddress,
            ]);
            self::assertNotNull($senderRule);
            self::assertSame('accept', $senderRule->getWbRule());
            self::assertSame(SenderRule::TYPE_IMPORT, $senderRule->getType());
        }

        // At domain creation, it needs to have a root '@.' RuleAddress (shared accross all domains).
        // Creating a domain also creates a SenderRule using this RuleAddress.
        self::assertSame(3, RuleAddressFactory::count());
        self::assertSame(3, SenderRuleFactory::count());
    }

    public function testUserRuleDoesNotReplaceGroupRule(): void
    {
        self::bootKernel();
        $user = UserFactory::new()->user()->create();
        $ruleAddress = (new RuleAddress())
            ->setEmail('sender@example.org')
            ->setPriority(6);
        $groupRule = (new SenderRule($user, $ruleAddress))
            ->setWbRule('block')
            ->setType(SenderRule::TYPE_GROUP)
            ->setPriority(SenderRule::PRIORITY_GROUP_OVERRIDE);
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->persist($ruleAddress);
        $entityManager->persist($groupRule);
        $entityManager->flush();

        $service = static::getContainer()->get(SenderRuleService::class);
        self::assertTrue($service->createOrUpdateForUserAndAliases(
            'sender@example.org',
            $user,
            'accept',
            SenderRule::TYPE_USER,
        ));

        $senderRuleRepository = static::getContainer()->get(SenderRuleRepository::class);
        $userRule = $senderRuleRepository->findOneBy([
            'user' => $user,
            'senderRuleAddress' => $ruleAddress,
            'priority' => SenderRule::PRIORITY_USER,
        ]);
        self::assertNotNull($userRule);
        self::assertSame('accept', $userRule->getWbRule());
        self::assertSame('block', $groupRule->getWbRule());
        self::assertSame(SenderRule::TYPE_GROUP, $groupRule->getType());
        self::assertSame(SenderRule::PRIORITY_GROUP_OVERRIDE, $groupRule->getPriority());
    }

    public function testUserRuleIsCreatedForMainUserAndAliases(): void
    {
        self::bootKernel();
        $domain = DomainFactory::createOne();
        $mainUser = UserFactory::new()->user($domain)->create();
        $alias = UserFactory::new()->user($domain)->create(['originalUser' => $mainUser]);
        self::assertSame($mainUser, $alias->getMainUser());

        $service = static::getContainer()->get(SenderRuleService::class);
        self::assertTrue($service->createOrUpdateForUserAndAliases(
            'sender@example.org',
            $alias,
            'block',
            SenderRule::TYPE_USER,
        ));

        $ruleAddress = static::getContainer()->get(RuleAddressRepository::class)->findOneBy([
            'email' => 'sender@example.org',
        ]);
        self::assertNotNull($ruleAddress);
        $senderRuleRepository = static::getContainer()->get(SenderRuleRepository::class);

        foreach ([$mainUser, $alias] as $recipient) {
            self::assertNotNull($senderRuleRepository->findOneBy([
                'user' => $recipient,
                'senderRuleAddress' => $ruleAddress,
                'priority' => SenderRule::PRIORITY_USER,
            ]));
        }
    }
}
