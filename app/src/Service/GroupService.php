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

    

    public function updateWblist(): void {


        $this->wblistRepository->deleteFromGroup();
        $this->wblistRepository->insertFromGroup();
    }

   

}
