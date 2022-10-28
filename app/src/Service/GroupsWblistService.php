<?php

namespace App\Service;

use App\Entity\Groups;
use App\Entity\GroupsWblist;
use App\Entity\Mailaddr;
use App\Entity\User;
use App\Entity\Wblist;
use App\Repository\GroupsWblistRepository;
use App\Repository\UserRepository;
use App\Repository\WblistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;

class GroupsWblistService {

    private UserRepository $userRepository;
    private GroupService $groupService;

    public function __construct(UserRepository $userRepository, GroupService $groupService) {

        $this->userRepository = $userRepository;
        $this->groupService = $groupService;
    }

    public function updateWblist(GroupsWblist $groupsWblist) {
        foreach ($groupsWblist->getGroups()->getUsers() as $user) {
            /* @var $user User */
                $this->groupService->updateWblistForUser($user, $groupsWblist->getGroups());
            $aliases = $this->userRepository->getListAliases($user);
            foreach($aliases as $alias){
                $this->groupService->updateWblistForUser($user, $groupsWblist->getGroups());    
            }
        }
    }

}
