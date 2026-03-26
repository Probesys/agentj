<?php

namespace App\Service;

use App\Amavis\MessageStatus;
use App\Message\AmavisRelease;
use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use App\Entity\User;
use App\Entity\Wblist;
use App\Repository\MailaddrRepository;
use App\Repository\MsgrcptRepository;
use App\Repository\UserRepository;
use App\Repository\WblistRepository;
use App\Service\CryptEncryptService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MessageService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageBusInterface $bus,
        private MailaddrRepository $mailaddrRepository,
        private MsgrcptRepository $messageRecipientRepository,
        private UserRepository $userRepository,
        private WblistRepository $wblistRepository,
        private CryptEncryptService $cryptEncryptService,
    ) {
    }

    /**
     * Authorize the message's sender for the given recipient and release the
     * messages that he sent to him.
     */
    public function authorizeSenderForRecipient(Msgrcpt $messageRecipient, int $validationSource): bool
    {
        $message = $messageRecipient->getMsgs();

        $senderEmail = $message->getSenderEmail();

        if (!$senderEmail) {
            return false;
        }

        $senderMailaddr = $this->mailaddrRepository->findOneOrCreateByEmail($senderEmail);

        $recipient = $messageRecipient->getRid();
        $userAndAliases = $this->userRepository->findUserAndAliasesByMaddr($recipient);

        foreach ($userAndAliases as $user) {
            $this->wblistRepository->updateOrCreateRule(
                $user,
                $senderMailaddr,
                wb: 'W',
                type: $validationSource,
                priority: Wblist::WBLIST_PRIORITY_USER,
            );

            $messageRecipientsToRelease = $this->messageRecipientRepository->findSentToUserByEmail($user, $senderEmail);

            foreach ($messageRecipientsToRelease as $messageRecipientToRelease) {
                $this->dispatchRelease($messageRecipientToRelease, MessageStatus::AUTHORIZED);
            }
        }

        // TODO do we need the message status?
        //$message->setStatus(MessageStatus::AUTHORIZED);
        //$this->em->persist($message);
        //$this->em->flush();

        return true;
    }

    /**
     * Ban the message's sender for the given recipient and reject the messages
     * that he sent to him.
     */
    public function banSenderForRecipient(Msgrcpt $messageRecipient, int $validationSource): bool
    {
        $message = $messageRecipient->getMsgs();

        $senderEmail = $message->getSenderEmail();

        if (!$senderEmail) {
            return false;
        }

        $senderMailaddr = $this->mailaddrRepository->findOneOrCreateByEmail($senderEmail);

        $recipient = $messageRecipient->getRid();
        $userAndAliases = $this->userRepository->findUserAndAliasesByMaddr($recipient);

        foreach ($userAndAliases as $user) {
            $this->wblistRepository->updateOrCreateRule(
                $user,
                $senderMailaddr,
                wb: 'B',
                type: $validationSource,
                priority: Wblist::WBLIST_PRIORITY_USER,
            );

            $messageRecipientsToBan = $this->messageRecipientRepository->findSentToUserByEmail($user, $senderEmail);

            foreach ($messageRecipientsToBan as $messageRecipientToBan) {
                if ($messageRecipientToBan->isVirus() || $messageRecipientToBan->isAlreadyReleased()) {
                    continue;
                }

                $messageRecipientToBan->setStatus(MessageStatus::BANNED);
                $this->messageRecipientRepository->save($messageRecipientToBan);
            }
        }

        // TODO do we need the message status?
        //$message->setStatus(MessageStatus::BANNED);
        //$this->em->persist($message);
        //$this->em->flush();

        return true;
    }

    /**
     * Restore (release) the message for the provided recipient.
     */
    public function dispatchRelease(Msgrcpt $messageRecipient, int $finalStatus = MessageStatus::RESTORED): void
    {
        if (
            $messageRecipient->isAlreadyReleased() ||
            $messageRecipient->isAmavisReleaseOngoing() ||
            $messageRecipient->isVirus()
        ) {
            return;
        }

        $messageRecipient->setAmavisReleaseStartedAt(new \DateTimeImmutable());
        $this->em->persist($messageRecipient);
        $this->em->flush();

        $this->bus->dispatch(new AmavisRelease(
            mailId: $messageRecipient->getMailIdAsString(),
            partitionTag: $messageRecipient->getPartitionTag(),
            rseqnum: $messageRecipient->getRseqnum(),
            finalStatus: $finalStatus
        ));
    }

    public function restore(Msgrcpt $messageRecipient): void
    {
        $this->dispatchRelease($messageRecipient, MessageStatus::RESTORED);
    }

    /**
     * Mark a message and its recipient as deleted.
     */
    public function delete(Msgs $message, Msgrcpt $messageRecipient): bool
    {
        $this->messageRecipientRepository->changeStatus(
            $message->getPartitionTag(),
            $message->getMailIdAsString(),
            MessageStatus::DELETED,
            $messageRecipient->getRid()->getId(),
        );

        return true;
    }

    /**
     * Return a secure token containing the id of the user and of the message.
     * The token is valid for 7 days.
     */
    public function getReleaseToken(Msgs $message, User $user): string
    {
        $data = $user->getId() . '%%%' . $message->getMailIdAsString();
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
