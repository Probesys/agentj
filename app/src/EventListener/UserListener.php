<?php

namespace App\EventListener;

use App\Entity\Groups;
use App\Entity\User;
use App\Entity\Wblist;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Router;

class UserListener {

  private $router ;

  public function __construct(Router $router) {
    $this->router = $router;
    
  }

  public function postUpdate(LifecycleEventArgs $args) {
    $this->index($args);
  }

  public function postPersist(LifecycleEventArgs $args) {
    $this->index($args);
  }

  public function index(LifecycleEventArgs $event) {
    $entity = $event->getEntity();

    $em = $event->getEntityManager();

    if ($entity instanceof User && $this->router->getContext()->getPathInfo() != '/admin/import/users') {

      $WblistRepository = $em->getRepository(Wblist::class);
      $WblistRepository->deleteUserGroup($entity->getId());
      $alias = $em->getRepository(User::class)->findBy(['originalUser' => $entity->getId()]);
      foreach ($alias as $userAlias) {
        $WblistRepository->deleteUserGroup($userAlias->getId());
      }
      
      /* @var $entity User */
      if ($entity->getGroups()) {
        $WblistRepository = $em->getRepository(Wblist::class);
        /* @var $group Groups */
        $group = $em->getRepository(Groups::class)->find($entity->getGroups()->getId());

        if ($group) {
          $result = $WblistRepository->deleteFromGroup($group->getId());
          $UserRepository = $em->getRepository(User::class);

          //Check if the group is active
          if ($result && $group->getActive()) {
            $WblistRepository->insertFromGroup($group->getId());
            //update policy for all user from group      
            $UserRepository->updatePolicyForGroup($group->getId());
          } elseif (!$group->getActive()) {
            //update policy for all user of a group from domain  
            $UserRepository->updatePolicyFromGroupToDomain($group->getId());
          }
        }
      }
    }
  }

}
