<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserService {


    private ParameterBagInterface $params;
    private EntityManagerInterface $em;
    private $userRepository;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, UserRepository $userRepository) {
        $this->params = $params;
        $this->em = $em;
        $this->userRepository = $userRepository;
    }

    /**
     * Update domain users policy with domain policy if user is not in a group
     * @param Domain $domain
     * @return void
     */
    public function updateUsersPolicyfromDomain(Domain $domain): void {
        $users = $this->userRepository->findBy([
            'domain' => $domain,
        ]);
        foreach ($users as $user) {

            /* @var $user User */
            $group = $this->userRepository->getMainUserGroup($user);
            if (!$group) {
                $user->setPolicy($domain->getPolicy());
                $this->em->persist($user);
                $this->em->flush();
            }
        }
    }

    /**
     * Update groups for user alaises
     * @param type $groupId
     * @return type
     */
    public function updateAliasGroupsFromUser(User $originalUser) {
        if ($originalUser) {
            $aliases = $this->userRepository->getListAliases($originalUser);
            foreach ($aliases as $alias) {
                /*@var $alias User */
                $alias->getGroups()->clear();
                $this->em->flush();
                foreach ($originalUser->getGroups() as $group) {
                    $alias->addGroup($group);
                }
                $this->em->flush();
            }
        }
    }

}
