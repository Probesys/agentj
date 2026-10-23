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
    public function syncUserAndAliases(User $user): void
    {
        $parentUser = $user->getMainUser();

        if ($user->isAlias()) {
            // If there is a parent, the user passed in argument is an alias.
            // We update this user using groups and rules from parent.
            $this->updateGroups($user, $parentUser->getGroups()->toArray());
            $this->updateSenderRules($user, $parentUser->getSenderRules()->toArray());
        } else {
            // Otherwise it's an original user, so we update all its aliases.
            $originalUserGroups = $parentUser->getGroups()->toArray();
            $originalUserSenderRules = $parentUser->getSenderRules()->toArray();

            $aliases = $parentUser->getAliases();
            foreach ($aliases as $alias) {
                $alias->setPolicy($parentUser->getPolicy());

                $this->updateGroups($alias, $originalUserGroups);
                $this->updateSenderRules($alias, $originalUserSenderRules);

                $this->em->persist($alias);
            }
        }

        $this->updateUserAndAliasPolicy($user);
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

        $rulesToAdd = array_udiff(
            $userSenderRules,
            $existingRules,
            fn(SenderRule $a, SenderRule $b) => $this->senderRuleKey($a) <=> $this->senderRuleKey($b),
        );

        // Add copies of missing rules.
        foreach ($rulesToAdd as $rule) {
            $newRule = SenderRule::copy($user, $rule);
            $this->em->persist($newRule);
            $user->addSenderRule($newRule);
        }

        // We should remove the rules that do no exist on parent user,
        // but when emails are sent from an alias, rules that are created
        // would be also removed.
    }

    /**
     * Compute a key to compare SenderRules.
     *
     * User ID is not part of that key, to be able to compare
     * SenderRules between users.
     */
    private function senderRuleKey(SenderRule $rule): string
    {
        return sprintf(
            '%d:%d',
            $rule->getSenderRuleAddress()->getId(),
            $rule->getPriority()
        );
    }
}
