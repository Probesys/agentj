<?php

namespace App\Service;

use App\Amavis\MessageStatus;
use App\Entity\Domain;
use App\Entity\Group;
use App\Entity\Message;
use App\Entity\MessageRecipient;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Message\AmavisRelease;
use App\Repository\DomainRepository;
use App\Repository\GroupRepository;
use App\Repository\MessageRecipientRepository;
use App\Repository\MessageRepository;
use App\Repository\RuleAddressRepository;
use App\Repository\SenderRuleRepository;
use App\Repository\UserRepository;
use App\Util\Email;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageService
{
    public function __construct(
        private MessageBusInterface $bus,
        private RuleAddressRepository $ruleAddressRepository,
        private MessageRecipientRepository $messageRecipientRepository,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private GroupRepository $groupRepository,
        private DomainRepository $domainRepository,
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

            // TODO: optimize not to call if loop
            $this->applyOnAllRecipients($user->getId(), $senderEmail, 'accept', 'user');
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
        $domain = $domainUser?->getDomain();

        $this->senderRuleRepository->updateOrCreateRule(
            $domainUser,
            $senderRuleAddress,
            wbRule: 'accept',
            type: $validationSource,
            priority: SenderRule::PRIORITY_DOMAIN,
        );

        $this->applyOnAllRecipients($domain->getId(), $senderEmail, 'accept', 'domain');

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

            $this->applyOnAllRecipients($user->getId(), $senderEmail, 'block', 'user');
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

        $this->applyOnAllRecipients($domainUser?->getDomain()->getId(), $senderEmail, 'block', 'domain');

        $message->setStatus(MessageStatus::BANNED);
        $this->messageRepository->save($message);

        return true;
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

    /**
     * @param 'user'|'group'|'domain' $type
     * @param 'accept'|'allow'|'block' $rule
     */
    public function applyOnAllRecipients(
        int $id,
        string $senderEmail,
        string $rule,
        string $type,
        bool $isUpdate = false,
    ): void {
        $user = null;
        $group = null;
        $domain = null;

        if ($type === 'user') {
            $user = $this->userRepository->findOneBy(['id' => $id]);
            $domain = $user->getDomain();
            $messageRecipients = $this->messageRecipientRepository->findSentToUserByEmail(
                $user,
                $senderEmail,
            );
        } elseif ($type === 'group') {
            $group = $this->groupRepository->findOneBy(['id' => $id]);
            $domain = $group->getDomain();
            $messageRecipients = $this->messageRecipientRepository->findSentToGroup(
                $group->getId(),
                $senderEmail,
            );
        } else {
            $domain = $this->domainRepository->findOneBy(['id' => $id]);
            $messageRecipients = $this->messageRecipientRepository->findSentToDomainByEmail(
                $domain,
                $senderEmail,
            );
        }

        $domainSpamLevel = $domain->getAuthorizedSendersSpamLevel();

        // FIXME: does not handle priority...
        foreach ($messageRecipients as $messageRecipientToRelease) {
            $isSameSender = $this->hasSameSender($messageRecipientToRelease, $senderEmail);
            $isSpam = match ($rule) {
                // always accept without checking spam level
                'allow' => false,
                // under domain's authorized spam level
                'accept' => $messageRecipientToRelease->isSpamAtLevel($domainSpamLevel),
                // do not need to check spam as it is blocked
                'block' => false,
            };

            if (!$isSameSender || $isSpam) {
                continue;
            }


            if ($type === 'group') {
                // TODO: do not try to apply if it already exists a User rule for this sender
                $currentUser = $this->userRepository->findOneByAddress($messageRecipientToRelease->getAddress());

                $rules = $this->senderRuleRepository->findBySenderEmailAndRecipient(
                    $senderEmail,
                    $messageRecipientToRelease->getAddress(),
                );
                // dump($currentUser->getId());
                // dump($rules[0]->getUser()->getId());
                // dump($senderEmail);
                // dump($rules[0]->getSenderRuleAddress()->getEmail());
                // dump($rules[0]->getWb());
                // dump($rules[0]->getPriority());
                // dump($rules[0]->getType());

                if (empty($rules)) {
                    continue;
                }

                // If it exists a SenderRule associated to a User for this sender, adding a group Rule should not update
                // MessageRecipient status as User's rules have higher priority than Group's rules.
                $highestPriorityGroup = $group;

                array_reduce(
                    $currentUser->getGroups()?->toArray(),
                    fn ($group1, $group2) => $group1 > $group2 ? $group1 : $group2,
                    $highestPriorityGroup,
                );

                if ($highestPriorityGroup !== $group && !$isUpdate) {
                    // If it's an update, the group has to have the hiqhest priority to trigger status update.
                    continue;
                }
            }

            $status = match ($rule) {
                'allow' => MessageStatus::AUTHORIZED, // TODO: check domain spam level to determine allow/block
                'accept' => MessageStatus::AUTHORIZED,
                'block' => MessageStatus::BANNED,
            };

            // FIXME: cannot use dispatchRelease as it save an amavisReleaseAt attributes
            // $this->dispatchRelease($messageRecipientToRelease, $status);
            $messageRecipientToRelease->setStatus($status);
            $this->messageRecipientRepository->save($messageRecipientToRelease);
        }
    }
}
