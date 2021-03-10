<?php

// src/EventListener/SwitchUserSubscriber.php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;

class SwitchUserSubscriber implements EventSubscriberInterface {

  private $security;

  public function __construct(Security $security) {
    $this->security = $security;
  }

  public function onSwitchUser(SwitchUserEvent $event) {
    /* @var User $user  */
    $user = $this->security->getUser();
    $token = $this->security->getToken();
    $userDomains = $user->getDomains()->toArray();

    $targetUser = $event->getTargetUser();

    $targetUserDomain = $targetUser->getDomain();

    if ($this->security->isGranted('ROLE_PREVIOUS_ADMIN')) {
      return true;
    }
    
    if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
      return true;
    }
    
    if ($this->security->isGranted('ROLE_USER') && in_array('ROLE_SUPER_ADMIN', $targetUser->getRoles())) {
      throw new AccessDeniedException('You are not allowed to swtich to this user');
    }
    
    //Check if the swicth user in in list of domains of the admin
    if ($this->security->isGranted('ROLE_ADMIN') && !in_array($targetUserDomain, $userDomains)) {
      throw new AccessDeniedException('You are not allowed to swtich to this user');
    }    

    
    //If a user attempt to switch to another user, the target user must be an alias of the original user or is shared with original user
    if ($this->security->isGranted('ROLE_USER') ) {
      
      //if target user is shared with original user
      if ($targetUser->getSharedWith()->contains($user)){
        return true;
      }
      
      
      if (!$targetUser->getOriginalUser()) {
        throw new AccessDeniedException("You are not allowed to switch to this user");
      } else {
        $originalUserEmail = stream_get_contents($user->getEmail(), -1, 0);
        $targetUserEmail = stream_get_contents($targetUser->getOriginalUser()->getEmail(), -1, 0);
        if ($originalUserEmail != $targetUserEmail) {
          throw new AccessDeniedException("You are not allowed to switch to this user");
        }
      }
      return true;
    }

  }

  public static function getSubscribedEvents() {
    return [
        SecurityEvents::SWITCH_USER => 'onSwitchUser',
    ];
  }

}
