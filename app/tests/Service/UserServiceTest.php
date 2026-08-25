<?php

namespace App\Tests\Service;

use App\Entity\Group;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Service\UserService;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\GroupFactory;
use App\Tests\Factory\PolicyFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
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

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();

        $this->userService = $container->get(UserService::class);
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
        // Create a user that is member of the 2 groups
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
        //   * group1 added
        //   * group2 kept
        //   * group3 removed
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
        // Create a user that is member of the 2 groups
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

        $this->userService->syncUserAndAliases($user);

        // Assert that policy has been updated for aliases.
        // Aliases should use the same policy as parent User.
        // Parent user's policy is recalculated from his groups.
        $this->refresh($user);
        $this->refresh($alias1);
        $this->refresh($alias2);
        self::assertSame($groupPolicy2, $user->getPolicy());
        self::assertSame($groupPolicy2, $alias1->getPolicy());
        self::assertSame($groupPolicy2, $alias2->getPolicy());
    }

    public function testSyncUserAndAliasesAddsSenderRulesToAliasesFromUser(): void
    {
        $policy = PolicyFactory::createOne();
        $domain = DomainFactory::createOne([
            'policy' => $policy,
        ]);
        // Create a user with 2 associated SenderRules
        $user = UserFactory::new()->user($domain)->create([
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

    public function testSyncUserAndAliasesDoesNotRemoveSenderRulesFromAliasesIfAbsentFromUser(): void
    {
        $policy = PolicyFactory::createOne();
        $domain = DomainFactory::createOne([
            'policy' => $policy,
        ]);
        $user = UserFactory::new()->user($domain)->create([
            'policy' => $policy,
        ]);
        // Create an alias with a rule missing from the main user. This case
        // can happen if a user sent an email with an alias email.
        $alias = UserFactory::new()->alias($user)->create([
            'policy' => null,
        ]);
        $senderRule = $this->createSenderRule('sender@' . $domain, $alias);
        self::assertEmpty($user->getSenderRules()->toArray());
        self::assertEqualsCanonicalizing(
            $this->getAssociatedSenderRuleIds([$senderRule]),
            $this->getAssociatedSenderRuleIds($alias->getSenderRules()->toArray()),
        );

        $this->userService->syncUserAndAliases($user);

        // Assert that SenderRules associated to alias hasn't been removed.
        $this->refresh($user);
        $this->refresh($alias);
        self::assertEmpty($user->getSenderRules()->toArray());
        self::assertEqualsCanonicalizing(
            $this->getAssociatedSenderRuleIds([$senderRule]),
            $this->getAssociatedSenderRuleIds($alias->getSenderRules()->toArray()),
        );
    }

    public function testSyncUserAndAliasesUpdatesGroupsForAnAlias(): void
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
        // Create a user that is member of the 2 groups
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

        $this->userService->syncUserAndAliases($alias1);

        // Assert that groups has been updated for user and alias1, but not alias2:
        //   * group1 added
        //   * group2 kept
        //   * group3 removed
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
            [$group2->getId(), $group3->getId()],
            $this->getAssociatedGroupIds($alias2),
        );
    }

    public function testSyncUserAndAliasesUpdatesPoliciesForAnAlias(): void
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
        // Create a user that is member of the 2 groups
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

        $this->userService->syncUserAndAliases($alias1);

        // Assert that policy has been updated for alias1.
        // Alias should use the same policy as parent User.
        // Parent user's policy isn't recalculated.
        $this->refresh($user);
        $this->refresh($alias1);
        $this->refresh($alias2);
        self::assertSame($userPolicy, $user->getPolicy());
        self::assertSame($userPolicy, $alias1->getPolicy());
        self::assertNull($alias2->getPolicy());
    }

    public function testSyncUserAndAliasesAddsSenderRulesToAliasFromUser(): void
    {
        $policy = PolicyFactory::createOne();
        $domain = DomainFactory::createOne([
            'policy' => $policy,
        ]);
        // Create a user with 2 associated SenderRules
        $user = UserFactory::new()->user($domain)->create([
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

        $this->userService->syncUserAndAliases($alias1);

        // Assert that SenderRules associated to user have been cloned
        // and associated to alias1.
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
        self::assertEmpty($alias2->getSenderRules()->toArray());
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
