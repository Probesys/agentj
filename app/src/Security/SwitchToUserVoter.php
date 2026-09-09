<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SwitchToUserVoter extends Voter
{
    public function __construct(
        private SwitchUserAuthorization $switchUserAuthorization,
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
        ?Vote $vote = null,
    ): bool {
        $user = $token->getUser();

        // Don't grant access if the user is anonymous or if the subject is not a user.
        if (!$user instanceof User || !$subject instanceof User) {
            return false;
        }

        return $this->switchUserAuthorization->canSwitch($user, $subject);
    }
}
