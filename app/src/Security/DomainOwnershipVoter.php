<?php

namespace App\Security;

use App\Entity\Domain;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'DOMAIN_ACCESS', Domain|HasDomainInterface>
 */
class DomainOwnershipVoter extends Voter
{
    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute !== 'DOMAIN_ACCESS') {
            return false;
        }

        return $subject instanceof Domain || $subject instanceof HasDomainInterface;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $domain = $subject instanceof Domain ? $subject : $subject->getDomain();

        if (!$domain instanceof Domain) {
            return false;
        }

        if ($this->security->isGrantedForUser($user, 'ROLE_SUPER_ADMIN')) {
            return true;
        }

        return $this->security->isGrantedForUser($user, 'ROLE_ADMIN') && $user->hasDomain($domain);
    }
}
