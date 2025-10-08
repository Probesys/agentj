<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\User;
use App\Repository\GroupsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private GroupsRepository $groupsRepository,
    ) {
    }

    /**
     * Update user and aliases policy with domain policy or group policy
     * @param User $user
     * @return void
     */
    public function updateUserAndAliasPolicy(User $user): void
    {
        $defaultGroup = $this->groupsRepository->getMainUserGroup($user);

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
            $group = $this->groupsRepository->getMainUserGroup($user);
            if (!$group) {
                $user->setPolicy($domain->getPolicy());
                $this->em->persist($user);
                $this->em->flush();
            }
        }
    }

    /**
     * Update groups and policy for user aliases
     */
    public function updateAliasGroupsAndPolicyFromUser(User $originalUser): void
    {
        $originalUserGroups = $originalUser->getGroups()->toArray();

        $aliases = $originalUser->getAliases();

        foreach ($aliases as $alias) {
            $alias->setPolicy($originalUser->getPolicy());

            $aliasGroups = $alias->getGroups()->toArray();

            $groupsToAdd = array_diff($originalUserGroups, $aliasGroups);
            $groupsToRemove = array_diff($aliasGroups, $originalUserGroups);

            foreach ($groupsToAdd as $group) {
                $alias->addGroup($group);
            }

            foreach ($groupsToRemove as $group) {
                $alias->removeGroup($group);
            }

            $this->em->persist($alias);
        }

        $this->em->flush();
    }
}
