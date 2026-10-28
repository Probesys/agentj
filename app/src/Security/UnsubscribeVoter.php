<?php

namespace App\Security;

use App\Amavis\MessageStatus;
use App\Entity\MessageRecipient;
use App\Entity\User;
use App\Util\Email;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Only the recipient (or the owner of the recipient alias) can unsubscribe,
 * and only from the mailing list messages which are not spam nor virus.
 *
 * @extends Voter<'UNSUBSCRIBE', MessageRecipient>
 */
class UnsubscribeVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'UNSUBSCRIBE' && $subject instanceof MessageRecipient;
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

        if (!$subject->getMessage()->getIsMlist()) {
            return false;
        }

        if (in_array($subject->getStatus(), [MessageStatus::SPAMMED, MessageStatus::VIRUS], true)) {
            return false;
        }

        return $this->isRecipientOf($user, $subject);
    }

    private function isRecipientOf(User $user, MessageRecipient $messageRecipient): bool
    {
        $recipientEmail = $messageRecipient->getAddress()?->getEmail();
        if ($recipientEmail === null) {
            return false;
        }

        $userEmails = [$user->getEmail()];
        foreach ($user->getAliases() ?? [] as $alias) {
            $userEmails[] = $alias->getEmail();
        }

        $recipientEmail = Email::normalize($recipientEmail);

        foreach ($userEmails as $userEmail) {
            if ($userEmail !== null && Email::normalize($userEmail) === $recipientEmail) {
                return true;
            }
        }

        return false;
    }
}
