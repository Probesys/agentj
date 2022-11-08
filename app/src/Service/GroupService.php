<?php

namespace App\Service;

use App\Entity\Groups;
use App\Entity\Mailaddr;
use App\Entity\User;
use App\Entity\Wblist;
use App\Repository\GroupsWblistRepository;
use App\Repository\UserRepository;
use App\Repository\WblistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;

class GroupService {

    private EntityManagerInterface $em;
    private WblistRepository $wblistRepository;
    private GroupsWblistRepository $groupsWblistRepository;

    public function __construct(EntityManagerInterface $em, WblistRepository $wblistRepository, GroupsWblistRepository $groupsWblistRepository) {
        $this->em = $em;
        $this->wblistRepository = $wblistRepository;
        $this->groupsWblistRepository = $groupsWblistRepository;
    }

    /**
     * Update user and aliases wblists from wblist defined in user group
     * @param User $user
     * @param array $oldGroups
     * @return void
     */
    public function updateWblistForUserAndAliases(User $user, array $oldGroups = []): void {

        $userRepository = $this->em->getRepository(User::class);
        $aliases = $userRepository->getListAliases($user);

        //Delete wblist from groups where user not in
        foreach ($oldGroups as $oldGroup) {
            if (!($user->getGroups()->contains($oldGroup))) {
                $this->wblistRepository->deleteUserWbListFromGroup($oldGroup, $user);
                foreach ($aliases as $alias) {
                    $this->wblistRepository->deleteUserWbListFromGroup($oldGroup, $alias);
                }
            }
        }

        $this->updateWblistForUser($user);
        /* @var $userRepository UserRepository */
        foreach ($aliases as $alias) {
            $this->updateWblistForUser($alias);
        }
    }

    /**
     * Update user wblists from wblist defined in his group
     * @param User $user
     * @return void
     */
    public function updateWblistForUser(User $user, Groups $group = null): void {
        $groups = $user->getGroups();
        if ($group) {
            $groups = [$group];
        }

        foreach ($groups as $group) {

            $this->wblistRepository->deleteUserWbListFromGroup($group, $user);
            if ($group->getActive()) {
                $priority = Wblist::WBLIST_PRIORITY_GROUP;
                if ($group->getOverrideUser()) {
                    $priority = Wblist::WBLIST_PRIORITY_GROUP_OVERRIDE;
                }

                /* @var $group Groups */
                $groupWblists = $this->groupsWblistRepository->getwbListforGroup($group);

                foreach ($groupWblists as $groupWblist) {
                    $mailaddr = $this->em->getRepository(Mailaddr::class)->find($groupWblist['id']);
                    $existingRule = $this->wblistRepository->getOneByUser($user, $mailaddr);
                    // if rule doest not exist 
                    if (!empty($groupWblist['wb']) && !$existingRule) {

                        $wbList = new Wblist($user, $mailaddr);
                        $wbList->setWb($groupWblist['wb']);
                        $wbList->setGroups($group);
                        $wbList->setPriority($priority);
                        $wbList->setType(Wblist::WBLIST_TYPE_GROUP);
                        $this->em->persist($wbList);
                    }

                    // if rule exist and group has hight priority we update it
                    if ($existingRule && $existingRule->getGroups()->getPriority() < $group->getPriority()) {
                        $existingRule->setWb($groupWblist['wb']);
                        $existingRule->setGroups($group);
                        $existingRule->setPriority($priority);
                        $existingRule->setType(Wblist::WBLIST_TYPE_GROUP);
                    }
                }
            }
            $this->em->flush();
        }
    }


}
