<?php

namespace App\Service;

use App\Amavis\MessageStatus;
use App\Entity\Domain;
use App\Entity\Message;
use App\Entity\MessageRecipient;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Message\AmavisRelease;
use App\Repository\GroupRepository;
use App\Repository\MessageRecipientRepository;
use App\Repository\MessageRepository;
use App\Repository\RuleAddressRepository;
use App\Repository\SenderRuleRepository;
use App\Repository\UserRepository;
use App\Util\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageService
{
    public function __construct(
        private MessageBusInterface $bus,
        private EntityManagerInterface $entityManager,
        private RuleAddressRepository $ruleAddressRepository,
        private MessageRecipientRepository $messageRecipientRepository,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private SenderRuleRepository $senderRuleRepository,
        private GroupRepository $groupRepository,
        private CryptEncryptService $cryptEncryptService,
        private SpamassassinService $spamassassinService,
    ) {
    }

    /**
     * Authorize the message's sender for the given recipient and release the
     * messages that he sent to him.
     */
    public function authorizeSenderForRecipient(MessageRecipient $messageRecipient, int $validationSource): bool
    {
        $message = $messageRecipient->getMessage();

        $senderEmail = $message->getSenderEmail();

        if (!$senderEmail) {
            return false;
        }

        $senderRuleAddress = $this->ruleAddressRepository->findOneOrCreateByEmail($senderEmail);

        $recipient = $messageRecipient->getAddress();
        $userAndAliases = $this->userRepository->findUserAndAliasesByAddress($recipient);

        foreach ($userAndAliases as $user) {
            $this->senderRuleRepository->updateOrCreateRule(
                $user,
                $senderRuleAddress,
                wbRule: 'accept',
                type: $validationSource,
                priority: SenderRule::PRIORITY_USER,
            );

            $messageRecipientsToRelease = $this->messageRecipientRepository->findSentToUserByEmail(
                $user,
                $message->getFromAddr(),
            );

            $domain = $user->getDomain();
            $domainSpamLevel = $domain->getAuthorizedSendersSpamLevel();

            foreach ($messageRecipientsToRelease as $messageRecipientToRelease) {
                $isSameSender = $this->hasSameSender($messageRecipientToRelease, $senderEmail);
                $isSpam = $messageRecipientToRelease->isSpamAtLevel($domainSpamLevel);
                if (!$isSameSender || $isSpam) {
                    continue;
                }

                $this->dispatchRelease($messageRecipientToRelease, MessageStatus::AUTHORIZED);
            }
        }

        $message->setStatus(MessageStatus::AUTHORIZED);
        $this->messageRepository->save($message);

        return true;
    }

    /**
     * Authorize the message's sender for the given recipient's domain and
     * release the messages that he sent to it.
     */
    public function authorizeSenderForDomain(MessageRecipient $messageRecipient, int $validationSource): bool
    {
        $message = $messageRecipient->getMessage();

        $senderEmail = $message->getSenderEmail();

        if (!$senderEmail) {
            return false;
        }

        $senderRuleAddress = $this->ruleAddressRepository->findOneOrCreateByEmail($senderEmail);

        $recipient = $messageRecipient->getAddress();
        $recipientDomainName = $recipient->getReverseDomain();

        $domainUser = $this->userRepository->findDomainUser($recipientDomainName);
        $domain = $domainUser->getDomain();
        $domainSpamLevel = $domain->getAuthorizedSendersSpamLevel();

        $this->senderRuleRepository->updateOrCreateRule(
            $domainUser,
            $senderRuleAddress,
            wbRule: 'accept',
            type: $validationSource,
            priority: SenderRule::PRIORITY_DOMAIN,
        );

        $messageRecipientsToRelease = $this->messageRecipientRepository->findSentToDomainByEmail(
            $domain,
            $message->getFromAddr(),
        );

        $messages = [];
        foreach ($messageRecipientsToRelease as $messageRecipientToRelease) {
            $isSameSender = $this->hasSameSender($messageRecipientToRelease, $senderEmail);
            $isSpam = $messageRecipientToRelease->isSpamAtLevel($domainSpamLevel);
            if (!$isSameSender || $isSpam) {
                continue;
            }

            $this->dispatchRelease($messageRecipientToRelease, MessageStatus::AUTHORIZED);
            $messages[] = $messageRecipientToRelease->getMessage();
        }

        foreach ($messages as $message) {
            $message->setStatus(MessageStatus::AUTHORIZED);
            $this->messageRepository->save($message, false);
        }
        $this->entityManager->flush();

        return true;
    }

    /**
     * Reprocess autorizations for the pending emails that are concerned by the
     * rule on given sender email.
     *
     * @param User $recipient Recipient for which pending mails will be reprocessed
     * (including mails sent to its aliases).
     * @param string $senderEmail Email address to authorize as legitimate sender.
     * @param bool $checkSpam Rather to apply domain's spam level check or not.
     */
    public function reprocessAuthorizations(User $recipient, string $senderEmail, bool $checkSpam = true): void
    {
        $senderRuleAddress = $this->ruleAddressRepository->findOneOrCreateByEmail($senderEmail);
        $userAndAliases = $recipient->getUserAndAliases();

        foreach ($userAndAliases as $user) {
            $this->senderRuleRepository->updateOrCreateRule(
                $user,
                $senderRuleAddress,
                wbRule: $checkSpam ? 'accept' : 'allow',
                type: SenderRule::TYPE_USER,
                priority: SenderRule::PRIORITY_USER,
                flush: false,
            );

            $domain = $user->getDomain();
            $domainSpamLevel = $domain->getAuthorizedSendersSpamLevel();

            $messageRecipientsToRelease = $this->messageRecipientRepository->findSentToUserByEmail(
                $user,
                $senderEmail,
            );

            $messages = [];

            foreach ($messageRecipientsToRelease as $messageRecipientToRelease) {
                $isSameSender = $this->hasSameSender($messageRecipientToRelease, $senderEmail);
                $isSpam = $checkSpam ?
                    $messageRecipientToRelease->isSpamAtLevel($domainSpamLevel) :
                    false;

                if (!$isSameSender || $isSpam) {
                    continue;
                }

                $this->dispatchRelease($messageRecipientToRelease, MessageStatus::AUTHORIZED);
                $messages[] = $messageRecipientToRelease->getMessage();
            }

            foreach ($messages as $message) {
                $message->setStatus(MessageStatus::AUTHORIZED);
                $this->messageRepository->save($message);
            }
        }
    }

    /**
     * Reprocess autorizations for the pending emails that are concerned by the
     * rule on given sender email.
     */
    public function reprocessAuthorizationsForSenderEmailInDomain(Domain $domain, string $senderEmail): void
    {
        $domainSpamLevel = $domain->getAuthorizedSendersSpamLevel();

        $messageRecipientsToRelease = $this->messageRecipientRepository->findSentToDomainByEmail(
            $domain,
            $senderEmail,
        );

        foreach ($messageRecipientsToRelease as $messageRecipientToRelease) {
            $isSameSender = $this->hasSameSender($messageRecipientToRelease, $senderEmail);
            $isSpam = $messageRecipientToRelease->isSpamAtLevel($domainSpamLevel);
            if (!$isSameSender || $isSpam) {
                continue;
            }

            $this->dispatchRelease($messageRecipientToRelease, MessageStatus::AUTHORIZED);
        }
    }

    /**
     * Reprocess autorizations for the pending emails that are concerned by the
     * rule on given sender email.
     */
    public function reprocessAuthorizationsForSenderEmailForUser(
        User $user,
        string $senderEmail,
        bool $checkSpam = true,
    ): void {
        $domain = $user->getDomain();
        $domainSpamLevel = $domain->getAuthorizedSendersSpamLevel();

        $messageRecipientsToRelease = $this->messageRecipientRepository->findSentToUserByEmail(
            $user,
            $senderEmail,
        );

        $messages = [];

        foreach ($messageRecipientsToRelease as $messageRecipientToRelease) {
            $isSameSender = $this->hasSameSender($messageRecipientToRelease, $senderEmail);
            $isSpam = $checkSpam ?
                $messageRecipientToRelease->isSpamAtLevel($domainSpamLevel) :
                false;

            if (!$isSameSender || $isSpam) {
                continue;
            }

            $this->dispatchRelease($messageRecipientToRelease, MessageStatus::AUTHORIZED);
            $messages[] = $messageRecipientToRelease->getMessage();
        }

        foreach ($messages as $message) {
            $message->setStatus(MessageStatus::AUTHORIZED);
            $this->messageRepository->save($message);
        }
    }

    /**
     * Ban the message's sender for the given recipient and reject the messages
     * that he sent to him.
     */
    public function banSenderForRecipient(MessageRecipient $messageRecipient, int $validationSource): bool
    {
        $message = $messageRecipient->getMessage();

        $senderEmail = $message->getSenderEmail();

        if (!$senderEmail) {
            return false;
        }

        $senderRuleAddress = $this->ruleAddressRepository->findOneOrCreateByEmail($senderEmail);

        $recipient = $messageRecipient->getAddress();
        $userAndAliases = $this->userRepository->findUserAndAliasesByAddress($recipient);

        foreach ($userAndAliases as $user) {
            $this->senderRuleRepository->updateOrCreateRule(
                $user,
                $senderRuleAddress,
                wbRule: 'block',
                type: $validationSource,
                priority: SenderRule::PRIORITY_USER,
            );

            $messageRecipientsToBan = $this->messageRecipientRepository->findSentToUserByEmail(
                $user,
                $message->getFromAddr()
            );

            foreach ($messageRecipientsToBan as $messageRecipientToBan) {
                $isSameSender = $this->hasSameSender($messageRecipientToBan, $senderEmail);
                if (
                    !$isSameSender ||
                    $messageRecipientToBan->isVirus() ||
                    $messageRecipientToBan->isAlreadyReleased()
                ) {
                    continue;
                }

                $messageRecipientToBan->setStatus(MessageStatus::BANNED);
                $this->messageRecipientRepository->save($messageRecipientToBan);
            }
        }

        $message->setStatus(MessageStatus::BANNED);
        $this->messageRepository->save($message);

        return true;
    }

    /**
     * Ban the message's sender for the given recipient's domain and reject the
     * messages that he sent to it.
     */
    public function banSenderForDomain(MessageRecipient $messageRecipient, int $validationSource): bool
    {
        $message = $messageRecipient->getMessage();

        $senderEmail = $message->getSenderEmail();

        if (!$senderEmail) {
            return false;
        }

        $senderRuleAddress = $this->ruleAddressRepository->findOneOrCreateByEmail($senderEmail);

        $recipient = $messageRecipient->getAddress();
        $recipientDomainName = $recipient->getReverseDomain();

        $domainUser = $this->userRepository->findDomainUser($recipientDomainName);

        $this->senderRuleRepository->updateOrCreateRule(
            $domainUser,
            $senderRuleAddress,
            wbRule: 'block',
            type: $validationSource,
            priority: SenderRule::PRIORITY_DOMAIN,
        );

        $messageRecipientsToBan = $this->messageRecipientRepository->findSentToDomainByEmail(
            $domainUser->getDomain(),
            $message->getFromAddr(),
        );

        $messages = [];
        foreach ($messageRecipientsToBan as $messageRecipientToBan) {
            $isSameSender = $this->hasSameSender($messageRecipientToBan, $senderEmail);
            if (
                !$isSameSender ||
                $messageRecipientToBan->isVirus() ||
                $messageRecipientToBan->isAlreadyReleased()
            ) {
                continue;
            }

            $messageRecipientToBan->setStatus(MessageStatus::BANNED);
            $this->messageRecipientRepository->save($messageRecipientToBan, false);

            $messages[] = $messageRecipientToBan->getMessage();
        }

        foreach ($messages as $message) {
            $message->setStatus(MessageStatus::BANNED);
            $this->messageRepository->save($message, false);
        }
        $this->entityManager->flush();

        return true;
    }

    /**
     * Reprocess bans for the pending emails that are concerned by the
     * rule on given sender email.
     *
     * @param User $recipient Recipient for which pending mails will be reprocessed
     * (including mails sent to its aliases).
     * @param string $senderEmail Email address to ban as rejected sender.
     */
    public function reprocessBans(User $recipient, string $senderEmail): void
    {
        $senderRuleAddress = $this->ruleAddressRepository->findOneOrCreateByEmail($senderEmail);
        $userAndAliases = $recipient->getUserAndAliases();

        foreach ($userAndAliases as $user) {
            $this->senderRuleRepository->updateOrCreateRule(
                $user,
                $senderRuleAddress,
                wbRule: 'block',
                type: SenderRule::TYPE_USER,
                priority: SenderRule::PRIORITY_USER,
            );

            $messageRecipientsToBan = $this->messageRecipientRepository->findSentToUserByEmail(
                $user,
                $senderEmail,
            );

            $messages = [];

            foreach ($messageRecipientsToBan as $messageRecipientToBan) {
                $isSameSender = $this->hasSameSender($messageRecipientToBan, $senderEmail);
                if (
                    !$isSameSender ||
                    $messageRecipientToBan->isVirus() ||
                    $messageRecipientToBan->isAlreadyReleased()
                ) {
                    continue;
                }

                $messageRecipientToBan->setStatus(MessageStatus::BANNED);
                $this->messageRecipientRepository->save($messageRecipientToBan);
                $messages[] = $messageRecipientToBan->getMessage();
            }

            foreach ($messages as $message) {
                $message->setStatus(MessageStatus::BANNED);
                $this->messageRepository->save($message);
            }
        }
    }

    /**
     * Reprocess bans for the pending emails that are concerned by the rule on
     * given sender email.
     */
    public function reprocessBansForSenderEmailInDomain(Domain $domain, string $senderEmail): void
    {
        $messageRecipientsToBan = $this->messageRecipientRepository->findSentToDomainByEmail(
            $domain,
            $senderEmail,
        );

        $messages = [];

        foreach ($messageRecipientsToBan as $messageRecipientToBan) {
            $isSameSender = $this->hasSameSender($messageRecipientToBan, $senderEmail);
            if (
                !$isSameSender ||
                $messageRecipientToBan->isVirus() ||
                $messageRecipientToBan->isAlreadyReleased()
            ) {
                continue;
            }

            $messageRecipientToBan->setStatus(MessageStatus::BANNED);
            $this->messageRecipientRepository->save($messageRecipientToBan);
            $messages[] = $messageRecipientToBan->getMessage();
        }

        foreach ($messages as $message) {
            $message->setStatus(MessageStatus::BANNED);
            $this->messageRepository->save($message);
        }
    }

    /**
     * @param iterable<MessageRecipient> $candidates
     * @return MessageRecipient[] the recipients that were banned
     */
    private function banMatchingRecipients(iterable $candidates, string $senderEmail): array
    {
        $banned = [];

        foreach ($candidates as $candidate) {
            if (
                !$this->hasSameSender($candidate, $senderEmail)
                || $candidate->isVirus()
                || $candidate->isAlreadyReleased()
            ) {
                continue;
            }

            $candidate->setStatus(MessageStatus::BANNED);
            $this->messageRecipientRepository->save($candidate, flush: false);
            $banned[] = $candidate;
        }

        return $banned;
    }

    /**
     * @param MessageRecipient[] $bannedRecipients
     */
    private function banMessages(array $bannedRecipients): void
    {
        $messages = [];
        foreach ($bannedRecipients as $recipient) {
            $message = $recipient->getMessage();
            $messages[] = $message;
        }

        foreach ($messages as $message) {
            $message->setStatus(MessageStatus::BANNED);
            $this->messageRepository->save($message, flush: false);
        }
    }

    /**
     * Reprocess bans for the pending emails that are concerned by the
     * rule on given sender email.
     */
    public function reprocessBansForSenderEmailForUser(User $user, string $senderEmail): void
    {
        $banned = $this->banMatchingRecipients(
            $this->messageRecipientRepository->findSentToUserByEmail($user, $senderEmail),
            $senderEmail,
        );
        $this->banMessages($banned);
        $this->entityManager->flush();
    }

    private function hasSameSender(MessageRecipient $messageRecipient, string $senderEmail): bool
    {
        $candidateSenderEmail = $messageRecipient->getMessage()->getSenderEmail();

        return $candidateSenderEmail !== null
            && Email::normalize($candidateSenderEmail) === Email::normalize($senderEmail);
    }

    /**
     * Restore (release) the message for the provided recipient.
     */
    public function dispatchRelease(
        MessageRecipient $messageRecipient,
        int $finalStatus = MessageStatus::RESTORED,
    ): void {
        if (
            $messageRecipient->isAlreadyReleased() ||
            $messageRecipient->isAmavisReleaseOngoing() ||
            $messageRecipient->isVirus()
        ) {
            return;
        }

        $messageRecipient->setAmavisReleaseStartedAt(new \DateTimeImmutable());
        $this->messageRecipientRepository->save($messageRecipient);

        $this->bus->dispatch(new AmavisRelease(
            mailId: $messageRecipient->getMailId(),
            partitionTag: $messageRecipient->getPartitionTag(),
            rseqnum: $messageRecipient->getRseqnum(),
            finalStatus: $finalStatus
        ));
    }

    public function restore(MessageRecipient $messageRecipient): void
    {
        $this->dispatchRelease($messageRecipient, MessageStatus::RESTORED);
    }

    /**
     * Mark a message and its recipient as deleted.
     */
    public function delete(Message $message, MessageRecipient $messageRecipient): bool
    {
        $this->messageRecipientRepository->changeStatus(
            $message->getPartitionTag(),
            $message->getMailId(),
            MessageStatus::DELETED,
            $messageRecipient->getAddress()->getId(),
        );

        return true;
    }

    /**
     * Mark a message as spam.
     *
     * Marking a message as spam put the message in a "spams" folder so
     * Spamassassin will learn with Bayes classifier. It also moves the message
     * to the "spam" menu for each recipient that would have it in "untreated"
     * menu.
     *
     * When $allowedDomains is given, only the recipients belonging to one of
     * these domains are moved; the others are left untouched.
     *
     * It returns false if the message couldn't be put in the spams folder.
     *
     * @param ?Domain[] $allowedDomains
     */
    public function markMessageAsSpam(Message $message, ?array $allowedDomains = null): bool
    {
        $result = $this->spamassassinService->marksAsSpam($message);

        $allowedDomainNames = $this->domainNames($allowedDomains);

        foreach ($message->getMessageRecipients() as $messageRecipient) {
            if (!$messageRecipient->isUntreated()) {
                continue;
            }

            if (!$this->isRecipientInDomains($messageRecipient, $allowedDomainNames)) {
                continue;
            }

            $messageRecipient->setStatus(MessageStatus::SPAMMED);
            $this->messageRecipientRepository->save($messageRecipient);
        }

        return $result;
    }

    /**
     * Mark a message as ham.
     *
     * Marking a message as ham put the message in a "hams" folder so
     * Spamassassin will learn with Bayes classifier. It also restores the
     * message for each recipient that would have it in "spam" menu.
     *
     * When $allowedDomains is given, only the recipients belonging to one of
     * these domains are restored; the others are left untouched.
     *
     * It returns false if the message couldn't be put in the hams folder.
     *
     * @param ?Domain[] $allowedDomains
     */
    public function markMessageAsHam(Message $message, ?array $allowedDomains = null): bool
    {
        $result = $this->spamassassinService->marksAsHam($message);

        $allowedDomainNames = $this->domainNames($allowedDomains);

        foreach ($message->getMessageRecipients() as $messageRecipient) {
            if (!$messageRecipient->isSpam()) {
                continue;
            }

            if (!$this->isRecipientInDomains($messageRecipient, $allowedDomainNames)) {
                continue;
            }

            $this->restore($messageRecipient);
        }

        return $result;
    }

    /**
     * @param ?Domain[] $domains
     * @return ?string[]
     */
    private function domainNames(?array $domains): ?array
    {
        if ($domains === null) {
            return null;
        }

        return array_map(
            fn (Domain $domain) => Email::normalize((string) $domain->getDomain()),
            $domains,
        );
    }

    /**
     * A null list of domains means "no restriction".
     *
     * @param ?string[] $domainNames
     */
    private function isRecipientInDomains(MessageRecipient $messageRecipient, ?array $domainNames): bool
    {
        if ($domainNames === null) {
            return true;
        }

        $recipientDomain = Email::normalize($messageRecipient->getAddress()->getReverseDomain());

        return in_array($recipientDomain, $domainNames, true);
    }

    /**
     * Return a secure token containing the id of the user and of the message.
     * The token is valid for 7 days.
     */
    public function getReleaseToken(Message $message, User $user): string
    {
        $data = $user->getId() . '%%%' . $message->getMailId();
        return $this->cryptEncryptService->encrypt($data, lifetime: 7 * 24 * 3600);
    }

    /**
     * Extract the data from a token (i.e. a user and a mail id).
     *
     * If the token is invalid or expired, it returns null.
     *
     * @return ?array{User, ?string}
     */
    public function decryptReleaseToken(string $token): ?array
    {
        try {
            list($expiry, $decryptedToken) = $this->cryptEncryptService->decrypt($token);
        } catch (\Exception) {
            return null;
        }

        if ($expiry < time()) {
            return null;
        }

        if ($decryptedToken === false) {
            return null;
        }

        $tokenParts = explode('%%%', $decryptedToken);

        if (count($tokenParts) !== 2) {
            return null;
        }

        $userId = (int) $tokenParts[0];
        $mailId = $tokenParts[1];

        $user = $this->userRepository->find($userId);

        if (!$user) {
            return null;
        }

        return [$user, $mailId];
    }


    public function getApplicableSenderRule(string $senderEmail, string $recipientEmail): ?string
    {
        return $this->senderRuleRepository->getApplicableSenderRule($senderEmail, $recipientEmail);
    }

    public function isSenderAuthorizedByRecipient(string $senderEmail, string $recipientEmail): bool
    {
        return $this->senderRuleRepository->isSenderAuthorizedByRecipient($senderEmail, $recipientEmail);
    }

    /**
     * @param array<int> $groupIds
     */
    public function reprocessForGroup(array $groupIds): void
    {
        dump($groupIds);
        foreach ($groupIds as $groupId) {
            $group = $this->groupRepository->findOneBy(['id' => $groupId]);

            $messageRecipients = $this->messageRecipientRepository->findSentToGroupMembersByEmail($group);
            foreach ($messageRecipients as $messageRecipient) {
                $senderEmail = $messageRecipient->getMessage()->getSenderEmail();

                $rule = $this->getApplicableSenderRule(
                    $senderEmail,
                    $messageRecipient->getAddress()->getEmail(),
                );

                if (!in_array($rule, ['allow', 'accept', 'block'], true)) {
                    continue;
                }

                $user = $this->userRepository->findOneByAddress($messageRecipient->getAddress());

                if (!$user) {
                    continue;
                }

                if ($rule === 'allow') {
                    // if ($this->isSenderAuthorizedByRecipient($senderEmail, $user->getEmail())) {
                        $this->reprocessAuthorizationsForSenderEmailForUser(
                            $user,
                            $senderEmail,
                            false,
                        );
                    // }
                } elseif ($rule === 'accept') {
                    // if ($this->isSenderAuthorizedByRecipient($senderEmail, $user->getEmail())) {
                        $this->reprocessAuthorizationsForSenderEmailForUser(
                            $user,
                            $senderEmail,
                        );
                    // }
                } else {
                    $this->reprocessBansForSenderEmailForUser(
                        $user,
                        $senderEmail,
                    );
                }
            }
        }
    }
}
