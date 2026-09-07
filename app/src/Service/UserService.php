<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\Group;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private GroupRepository $groupRepository,
    ) {
    }

    /**
     * Update user and aliases policy with domain policy or group policy
     * @param User $user
     * @return void
     */
    public function updateUserAndAliasPolicy(User $user): void
    {
        $defaultGroup = $this->groupRepository->getMainUserGroup($user);

        $policy = $defaultGroup ? $defaultGroup->getPolicy() : $user->getDomain()->getPolicy();
        $user->setPolicy($policy);
        $this->em->persist($user);
        $aliases = $user->getAliases();
        foreach ($aliases as $alias) {
            $alias->setPolicy($policy);
            $this->em->persist($alias);
        }
    }

    /**
     * Update domain users policy with domain policy if user is not in a group
     * @param Domain $domain
     * @return void
     */
    public function updateUsersPolicyfromDomain(Domain $domain): void
    {
        $users = $this->userRepository->findBy([
            'domain' => $domain,
        ]);
        foreach ($users as $user) {
            /* @var $user User */
            $group = $this->groupRepository->getMainUserGroup($user);
            if (!$group) {
                $user->setPolicy($domain->getPolicy());
                $this->em->persist($user);
                $this->em->flush();
            }
        }
    }

    /**
     * Update groups and policy for user and its aliases
     */
    public function updateAliasGroupsAndPolicyFromUser(User $user): void
    {
        $parentUser = $user->getOriginalUser();
        if ($parentUser) {
            // If there is a parent, current user is an alias => update using groups and rules from parent
            $this->updateGroups($user, $parentUser->getGroups()->toArray());
            $this->updateSenderRules($user, $parentUser->getSenderRules()->toArray());
        } else {
            // Otherwise it's an original user
            $originalUserGroups = $user->getGroups()->toArray();
            $originalUserSenderRules = $user->getSenderRules()->toArray();

            $aliases = $user->getAliases();
            foreach ($aliases as $alias) {
                $alias->setPolicy($user->getPolicy());

                $this->updateGroups($alias, $originalUserGroups);
                $this->updateSenderRules($alias, $originalUserSenderRules);

                $this->em->persist($alias);
            }
        }

        $this->em->flush();
    }

        /**
     * @param array<Group> $userGroups
     */
    private function updateGroups(User $user, array $userGroups): void
    {
        $aliasGroups = $user->getGroups()->toArray();

        $groupsToAdd = array_udiff(
            $userGroups,
            $aliasGroups,
            fn(Group $a, Group $b) => $a->getId() <=> $b->getId()
        );

        $groupsToRemove = array_udiff(
            $aliasGroups,
            $userGroups,
            fn(Group $a, Group $b) => $a->getId() <=> $b->getId()
        );

        foreach ($groupsToAdd as $group) {
            $user->addGroup($group);
        }

        foreach ($groupsToRemove as $group) {
            $user->removeGroup($group);
        }
    }

    /**
     * @param array<SenderRule> $userSenderRules
     */
    private function updateSenderRules(User $user, array $userSenderRules): void
    {
        $existingRules = $user->getSenderRules()->toArray();
        $existingMap = [];
        foreach ($existingRules as $rule) {
            $existingMap[$this->senderRuleKey($rule)] = $rule;
        }

        $sourceMap = [];
        foreach ($userSenderRules as $rule) {
            $sourceMap[$this->senderRuleKey($rule)] = $rule;
        }

        // Remove rules that shouldn't exist on this user.
        foreach ($existingRules as $existingRule) {
            $key = $this->senderRuleKey($existingRule);

            if (!isset($sourceMap[$key])) {
                $user->removeSenderRule($existingRule);
                $this->em->remove($existingRule);
            }
        }

        // Add copies of missing rules.
        foreach ($sourceMap as $key => $sourceRule) {
            $existingRule = $existingMap[$this->senderRuleKey($sourceRule)] ?? null;

            if ($existingRule === null) {
                // As a SenderRule is associated to a unique User, we have to clone it and associate to new User.
                $newRule = new SenderRule($user, $sourceRule->getSenderRuleAddress());
                $newRule->setPriority($sourceRule->getPriority());
                $newRule->setWb($sourceRule->getWb());

                $this->em->persist($newRule);
                $user->addSenderRule($newRule);
            }
        }
    }

    private function senderRuleKey(SenderRule $rule): string
    {
        return sprintf(
            '%d:%d',
            $rule->getSenderRuleAddress()->getId(),
            $rule->getPriority()
        );
    }
}
