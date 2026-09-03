<?php

namespace App\Service;

use App\Amavis\MessageStatus;
use App\Entity\Message;
use App\Entity\MessageRecipient;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Message\AmavisRelease;
use App\Repository\MessageRecipientRepository;
use App\Repository\MessageRepository;
use App\Repository\RuleAddressRepository;
use App\Repository\SenderRuleRepository;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageService
{
    public function __construct(
        private MessageBusInterface $bus,
        private RuleAddressRepository $ruleAddressRepository,
        private MessageRecipientRepository $messageRecipientRepository,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private SenderRuleRepository $senderRuleRepository,
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

        // Sender rules are case-insensitive
        $normalizedEmail = strtolower($senderEmail);
        $senderRuleAddress = $this->ruleAddressRepository->findOneOrCreateByEmail($normalizedEmail);

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
                $isSameSender = $messageRecipientToRelease->getMessage()->getSenderEmail() === $senderEmail;
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
            priority: SenderRule::PRIORITY_USER,
        );

        $messageRecipientsToRelease = $this->messageRecipientRepository->findSentToDomainByEmail(
            $domain,
            $message->getFromAddr(),
        );

        foreach ($messageRecipientsToRelease as $messageRecipientToRelease) {
            $isSameSender = $messageRecipientToRelease->getMessage()->getSenderEmail() === $senderEmail;
            $isSpam = $messageRecipientToRelease->isSpamAtLevel($domainSpamLevel);
            if (!$isSameSender || $isSpam) {
                continue;
            }

            $this->dispatchRelease($messageRecipientToRelease, MessageStatus::AUTHORIZED);
        }

        $message->setStatus(MessageStatus::AUTHORIZED);
        $this->messageRepository->save($message);

        return true;
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
                $isSameSender = $messageRecipientToBan->getMessage()->getSenderEmail() === $senderEmail;
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
            priority: SenderRule::PRIORITY_USER,
        );

        $messageRecipientsToBan = $this->messageRecipientRepository->findSentToDomainByEmail(
            $domainUser->getDomain(),
            $message->getFromAddr(),
        );

        foreach ($messageRecipientsToBan as $messageRecipientToBan) {
            $isSameSender = $messageRecipientToBan->getMessage()->getSenderEmail() === $senderEmail;
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

        $message->setStatus(MessageStatus::BANNED);
        $this->messageRepository->save($message);

        return true;
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
     * It returns false if the message couldn't be put in the spams folder.
     */
    public function markMessageAsSpam(Message $message): bool
    {
        $result = $this->spamassassinService->marksAsSpam($message);

        foreach ($message->getMessageRecipients() as $messageRecipient) {
            if (!$messageRecipient->isUntreated()) {
                continue;
            }

            $recipient = $messageRecipient->getAddress();
            $recipientEmail = $recipient->getEmail();

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
     * It returns false if the message couldn't be put in the hams folder.
     */
    public function markMessageAsHam(Message $message): bool
    {
        $result = $this->spamassassinService->marksAsHam($message);

        foreach ($message->getMessageRecipients() as $messageRecipient) {
            if (!$messageRecipient->isSpam()) {
                continue;
            }

            $this->restore($messageRecipient);
        }

        return $result;
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
        list($expiry, $decryptedToken) = $this->cryptEncryptService->decrypt($token);

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
}
