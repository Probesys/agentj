<?php

namespace App\Tests\Service;

use App\Entity\Domain;
use App\Entity\Group;
use App\Entity\GroupRule;
use App\Entity\RuleAddress;
use App\Entity\SenderRule;
use App\Service\GroupService;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\GroupFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class GroupServiceTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use FactoryHelper;

    public function testUpdateSenderRulesOnlyUpdatesGivenGroups(): void
    {
        self::bootKernel();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $group1 = $this->createGroup($domain, 1);
        $group2 = $this->createGroup($domain, 2);
        $ruleAddress1 = RuleAddressFactory::createOne(['email' => 'sender1@example.org']);
        $ruleAddress2 = RuleAddressFactory::createOne(['email' => 'sender2@example.org']);
        $user->addGroup($group1);
        $user->addGroup($group2);
        $groupRule1 = $this->createGroupRule($group1, $ruleAddress1, 'block');
        $groupRule2 = $this->createGroupRule($group2, $ruleAddress2, 'block');
        $groupService = static::getContainer()->get(GroupService::class);
        $groupService->updateSenderRules();

        $groupRule1->setWbRule('allow');
        $groupRule2->setWbRule('allow');
        $this->getEntityManager()->flush();
        $groupService->updateSenderRules([$group1]);

        SenderRuleFactory::assert()->exists([
            'user' => $user,
            'senderRuleAddress' => $ruleAddress1,
            'group' => $group1,
            'wb' => 'W',
            'type' => SenderRule::TYPE_GROUP,
            'priority' => SenderRule::PRIORITY_GROUP + 1,
        ]);
        SenderRuleFactory::assert()->exists([
            'user' => $user,
            'senderRuleAddress' => $ruleAddress2,
            'group' => $group2,
            'wb' => 'B',
            'type' => SenderRule::TYPE_GROUP,
            'priority' => SenderRule::PRIORITY_GROUP + 2,
        ]);
    }

    public function testUpdateSenderRulesRemovesRulesOfUserRemovedFromGroup(): void
    {
        self::bootKernel();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $group = $this->createGroup($domain, 1);
        $ruleAddress = RuleAddressFactory::createOne(['email' => 'sender@example.org']);
        $user->addGroup($group);
        $this->createGroupRule($group, $ruleAddress, 'block');
        $groupService = static::getContainer()->get(GroupService::class);
        $groupService->updateSenderRules([$group]);
        SenderRuleFactory::assert()->exists([
            'user' => $user,
            'senderRuleAddress' => $ruleAddress,
            'group' => $group,
        ]);

        $user->removeGroup($group);
        $this->getEntityManager()->flush();
        $groupService->updateSenderRules([$group]);

        SenderRuleFactory::assert()->notExists([
            'user' => $user,
            'senderRuleAddress' => $ruleAddress,
        ]);
    }

    public function testUpdateSenderRulesWithoutArgumentUpdatesAllGroups(): void
    {
        self::bootKernel();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $group1 = $this->createGroup($domain, 1);
        $group2 = $this->createGroup($domain, 2);
        $ruleAddress1 = RuleAddressFactory::createOne(['email' => 'sender1@example.org']);
        $ruleAddress2 = RuleAddressFactory::createOne(['email' => 'sender2@example.org']);
        $user->addGroup($group1);
        $user->addGroup($group2);
        $this->createGroupRule($group1, $ruleAddress1, 'block');
        $this->createGroupRule($group2, $ruleAddress2, 'allow');

        $groupService = static::getContainer()->get(GroupService::class);
        $groupService->updateSenderRules();

        SenderRuleFactory::assert()->exists([
            'user' => $user,
            'senderRuleAddress' => $ruleAddress1,
            'group' => $group1,
            'wb' => 'B',
        ]);
        SenderRuleFactory::assert()->exists([
            'user' => $user,
            'senderRuleAddress' => $ruleAddress2,
            'group' => $group2,
            'wb' => 'W',
        ]);
    }

    private function createGroup(Domain $domain, int $priority): Group
    {
        return GroupFactory::createOne([
            'domain' => $domain,
            'priority' => $priority,
            'active' => true,
            'overrideUser' => false,
        ]);
    }

    /**
     * @param 'accept'|'block'|'allow' $wbRule
     */
    private function createGroupRule(Group $group, RuleAddress $ruleAddress, string $wbRule): GroupRule
    {
        $groupRule = new GroupRule();
        $groupRule->setGroup($group);
        $groupRule->setRuleAddress($ruleAddress);
        $groupRule->setWbRule($wbRule);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($groupRule);
        $entityManager->flush();

        return $groupRule;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
