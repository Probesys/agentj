<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class SwitchUserAuthorization
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function canSwitch(User $user, User $targetUser): bool
    {
        if ($this->security->isGrantedForUser($user, 'ROLE_SUPER_ADMIN')) {
            return true;
        }

        if ($this->security->isGrantedForUser($targetUser, 'ROLE_SUPER_ADMIN')) {
            return false;
        }

        $targetDomain = $targetUser->getDomain();
        if (
            $this->security->isGrantedForUser($user, 'ROLE_ADMIN')
            && $targetDomain !== null
            && $user->hasDomain($targetDomain)
        ) {
            return true;
        }

        return $this->security->isGrantedForUser($user, 'ROLE_USER')
            && $targetUser->isSharedWith($user);
    }
}
