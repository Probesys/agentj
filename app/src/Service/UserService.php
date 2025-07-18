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
        $aliases = $this->userRepository->getListAliases($user);
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
     * @param User $originalUser
     * @return void
     */
    public function updateAliasGroupsAndPolicyFromUser(User $originalUser): void
    {
        $aliases = $this->userRepository->getListAliases($originalUser);
        foreach ($aliases as $alias) {
            /* @var $alias User */
            $alias->getGroups()->clear();
            $this->em->flush();
            foreach ($originalUser->getGroups() as $group) {
                $alias->addGroup($group);
            }
            $alias->setPolicy($originalUser->getPolicy());
            $this->em->flush();
        }
    }
}
