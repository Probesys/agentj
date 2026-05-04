<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SwitchToUserVoter extends Voter
{
    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'CAN_SWITCH_USER' && $subject instanceof User;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
    ): bool {
        $user = $token->getUser();

        // Don't grant access if the user is anonymous or if the subject is not a user.
        if (!$user instanceof User || !$subject instanceof User) {
            return false;
        }

        $targetUser = $subject;
        $targetUserDomain = $targetUser->getDomain();

        // Super admins can switch to any user they want.
        if ($this->security->isGrantedForUser($user, 'ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Make sure that only super admins can impersonate other super admins.
        if ($this->security->isGrantedForUser($targetUser, 'ROLE_SUPER_ADMIN')) {
            return false;
        }

        // Admins can only switch to users being part of the domains they manage.
        if (
            $this->security->isGrantedForUser($user, 'ROLE_ADMIN') &&
            $user->hasDomain($targetUserDomain)
        ) {
            return true;
        }

        // If a user attempts to switch to another user, the target user must
        // be shared with the user.
        if (
            $this->security->isGrantedForUser($user, 'ROLE_USER') &&
            $targetUser->isSharedWith($user)
        ) {
            return true;
        }

        return false;
    }
}
