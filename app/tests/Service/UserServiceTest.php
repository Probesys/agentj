<?php

namespace App\Tests\Service;

use App\Entity\Group;
use App\Entity\Policy;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Repository\SenderRuleRepository;
use App\Service\UserService;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\GroupFactory;
use App\Tests\Factory\PolicyFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserServiceTest extends WebTestCase
{
    use FactoryHelper;
    use Factories;
    use MessageHelper;
    use ResetDatabase;

    private UserService $userService;
    private EntityManagerInterface $entityManager;
    private SenderRuleRepository $senderRuleRepository;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();

        $this->userService = $container->get(UserService::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->senderRuleRepository = $container->get(SenderRuleRepository::class);
    }

    public function testSyncUserAndAliasesUpdatesGroupsForUserAndAliases(): void
    {
        $policy = PolicyFactory::createOne();
        $domain = DomainFactory::createOne([
            'policy' => $policy,
        ]);
        $group1 = GroupFactory::createOne([
            'domain' => $domain,
            'policy' => $policy,
        ]);
        $group2 = GroupFactory::createOne([
            'domain' => $domain,
            'policy' => $policy,
        ]);
        $group3 = GroupFactory::createOne([
            'domain' => $domain,
            'policy' => $policy,
        ]);
        // // Create a user that is member of the 2 groups
        $user = UserFactory::new()->user($domain)->create([
            'groups' => [$group1, $group2],
            'policy' => $policy,
        ]);
        // Create 2 aliases for this user
        $alias1 = UserFactory::new()->alias($user)->create([
            'policy' => $user->getPolicy(),
            'groups' => [
                $group2,
                $group3,
            ]
        ]);
        $alias2 = UserFactory::new()->alias($user)->create([
            'policy' => $user->getPolicy(),
            'groups' => [
                $group2,
                $group3,
            ]
        ]);

        $this->userService->syncUserAndAliases($user);

        // Assert that groups has been updated for user and aliases:
        //   * group1 removed
        //   * group2 kept
        //   * group3 added
        $this->refresh($user);
        $this->refresh($alias1);
        $this->refresh($alias2);
        self::assertEqualsCanonicalizing(
            [$group1->getId(), $group2->getId()],
            $this->getAssociatedGroupIds($user),
        );
        self::assertEqualsCanonicalizing(
            [$group1->getId(), $group2->getId()],
            $this->getAssociatedGroupIds($alias1),
        );
        self::assertEqualsCanonicalizing(
            [$group1->getId(), $group2->getId()],
            $this->getAssociatedGroupIds($alias2),
        );
    }

    public function testSyncUserAndAliasesUpdatesPoliciesForUserAndAliases(): void
    {
        $domainPolicy = PolicyFactory::createOne();
        $groupPolicy1 = PolicyFactory::createOne();
        $groupPolicy2 = PolicyFactory::createOne();
        $userPolicy = PolicyFactory::createOne();
        $domain = DomainFactory::createOne([
            'policy' => $domainPolicy,
        ]);
        $group1 = GroupFactory::createOne([
            'domain' => $domain,
            'policy' => $groupPolicy1,
            'priority' => 12,
        ]);
        $group2 = GroupFactory::createOne([
            'domain' => $domain,
            'policy' => $groupPolicy2,
            'priority' => 42,
        ]);
        // // Create a user that is member of the 2 groups
        $user = UserFactory::new()->user($domain)->create([
            'groups' => [$group1, $group2],
            'policy' => $userPolicy,
        ]);
        // Create 2 aliases for this user
        $alias1 = UserFactory::new()->alias($user)->create([
            'policy' => null,
        ]);
        $alias2 = UserFactory::new()->alias($user)->create([
            'policy' => null,
        ]);
        self::assertSame($userPolicy, $user->getPolicy());
        self::assertNull($alias1->getPolicy());
        self::assertNull($alias2->getPolicy());

        $this->userService->syncUserAndAliases($user);

        // Assert that policy has been updated for user and aliases.
        // Group policy has the priority over user Policy.
        // $groupPolicy2 is used, as $group2 priority is highest.
        $this->refresh($user);
        $this->refresh($alias1);
        $this->refresh($alias2);
        self::assertSame($groupPolicy2, $user->getPolicy());
        self::assertSame($groupPolicy2, $alias1->getPolicy());
        self::assertSame($groupPolicy2, $alias2->getPolicy());
    }

    public function testSyncUserAndAliasesUpdatesAddSenderRulesForUserAndAliases(): void
    {
        $policy = PolicyFactory::createOne();
        $domain = DomainFactory::createOne([
            'policy' => $policy,
        ]);
        $group = GroupFactory::createOne([
            'domain' => $domain,
            'policy' => $policy,
        ]);
        // Create a user with 2 associated SenderRules
        $user = UserFactory::new()->user($domain)->create([
            'groups' => [$group],
            'policy' => $policy,
        ]);
        $senderRule1 = $this->createSenderRule('sender1@' . $domain, $user);
        $senderRule2 = $this->createSenderRule('sender2@' . $domain, $user);
        // Create 2 aliases for this user
        $alias1 = UserFactory::new()->alias($user)->create([
            'policy' => null,
        ]);
        $alias2 = UserFactory::new()->alias($user)->create([
            'policy' => null,
        ]);
        self::assertEqualsCanonicalizing(
            $this->getAssociatedSenderRuleIds([$senderRule1, $senderRule2]),
            $this->getAssociatedSenderRuleIds($user->getSenderRules()->toArray()),
        );
        self::assertEmpty($alias1->getSenderRules()->toArray());
        self::assertEmpty($alias2->getSenderRules()->toArray());

        $this->userService->syncUserAndAliases($user);

        // Assert that SenderRules associated to user have been cloned
        // and associated to the 2 user's aliases.
        $this->refresh($user);
        $this->refresh($alias1);
        $this->refresh($alias2);
        self::assertEqualsCanonicalizing(
            $this->getAssociatedSenderRuleIds([$senderRule1, $senderRule2]),
            $this->getAssociatedSenderRuleIds($user->getSenderRules()->toArray()),
        );
        self::assertEqualsCanonicalizing(
            $this->getAssociatedSenderRuleIds([$senderRule1, $senderRule2]),
            $this->getAssociatedSenderRuleIds($alias1->getSenderRules()->toArray()),
        );
        self::assertEqualsCanonicalizing(
            $this->getAssociatedSenderRuleIds([$senderRule1, $senderRule2]),
            $this->getAssociatedSenderRuleIds($alias2->getSenderRules()->toArray()),
        );
    }

    public function testSyncUserAndAliasesUpdatesRemoveSenderRulesForUserAndAliases(): void
    {
        $policy = PolicyFactory::createOne();
        $domain = DomainFactory::createOne([
            'policy' => $policy,
        ]);
        $group = GroupFactory::createOne([
            'domain' => $domain,
            'policy' => $policy,
        ]);
        // Create a user with 2 associated SenderRules
        $user = UserFactory::new()->user($domain)->create([
            'groups' => [$group],
            'policy' => $policy,
        ]);
        $senderAddress = RuleAddressFactory::new()->create([
            'priority' => 6,
            'email' => 'sender@' . $domain,
        ]);
        $senderRule1 = SenderRuleFactory::new()->create([
            'user' => $user,
            'senderRuleAddress' => $senderAddress,
            'wb' => 'accept',
            'priority' => SenderRule::PRIORITY_USER,
        ]);
        // Create 2 aliases for this user, with 2 SenderRules associated
        $alias1 = UserFactory::new()->alias($user)->create([
            'policy' => null,
        ]);
        $senderRule1Alias1 = SenderRuleFactory::new()->create([
            'user' => $alias1,
            'senderRuleAddress' => $senderAddress,
            'wb' => 'accept',
            'priority' => SenderRule::PRIORITY_USER,
        ]);
        $alias2 = UserFactory::new()->alias($user)->create([
            'policy' => null,
        ]);
        $senderRule1Alias2 = SenderRuleFactory::new()->create([
            'user' => $alias2,
            'senderRuleAddress' => $senderAddress,
            'wb' => 'accept',
            'priority' => SenderRule::PRIORITY_USER,
        ]);
        self::assertEqualsCanonicalizing(
            $this->getAssociatedSenderRuleIds([$senderRule1]),
            $this->getAssociatedSenderRuleIds($user->getSenderRules()->toArray()),
        );
        self::assertEqualsCanonicalizing(
            $this->getAssociatedSenderRuleIds([$senderRule1Alias1]),
            $this->getAssociatedSenderRuleIds($alias1->getSenderRules()->toArray()),
        );
        self::assertEqualsCanonicalizing(
            $this->getAssociatedSenderRuleIds([$senderRule1Alias2]),
            $this->getAssociatedSenderRuleIds($alias2->getSenderRules()->toArray()),
        );

        // Then, remove sender rule from user1
        $this->entityManager->remove($senderRule1);
        $this->senderRuleRepository->delete(
            $user->getId(),
            $senderRule1->getSenderRuleAddress()->getId(),
            $senderRule1->getPriority(),
        );
        $this->refresh($user);
        $this->userService->syncUserAndAliases($user);

        // Assert that SenderRules associated to user have been cloned
        // and associated to the 2 user's aliases.
        $this->refresh($user);
        $this->refresh($alias1);
        $this->refresh($alias2);
        self::assertEmpty($user->getSenderRules());
        self::assertEmpty($alias1->getSenderRules());
        self::assertEmpty($alias2->getSenderRules());
    }

    /**
     * @return array<int>
     */
    private function getAssociatedGroupIds(User $user): array
    {
        return array_map(
            fn (Group $group) => $group->getId(),
            $user->getGroups()->toArray(),
        );
    }

    /**
     * @param SenderRule[] $rules
     * @return array<string>
     */
    private function getAssociatedSenderRuleIds(array $rules): array
    {
        return array_map(
            fn (SenderRule $rule) => $this->senderRulePrimaryKey($rule),
            $rules,
        );
    }

    /**
     * Compute a string derived of the primary key of the SenderRule.
     */
    private function senderRulePrimaryKey(SenderRule $rule): string
    {
        return sprintf(
            '%d:%d',
            $rule->getSenderRuleAddress()->getId(),
            $rule->getPriority()
        );
    }
}
