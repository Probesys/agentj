<?php

namespace App\Service;

use App\Amavis\MessageStatus;
use App\Message\AmavisRelease;
use App\Entity;
use App\Repository\MsgrcptRepository;
use App\Repository\UserRepository;
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
        private MsgrcptRepository $msgrcptRepository,
        private UserRepository $userRepository,
        private CryptEncryptService $cryptEncryptService,
    ) {
    }

    /**
     * Authorize the message's sender and release the message.
     *
     * @param Entity\Msgrcpt[] $messageRecipients
     */
    public function authorize(Entity\Msgs $message, array $messageRecipients, int $validationSource): bool
    {
        return $this->msgsToWblist($message, $messageRecipients, 'W', $validationSource);
    }

    /**
     * Ban the message's sender and reject the message.
     *
     * @param Entity\Msgrcpt[] $messageRecipients
     */
    public function ban(Entity\Msgs $message, array $messageRecipients, int $validationSource): bool
    {
        return $this->msgsToWblist($message, $messageRecipients, 'B', $validationSource);
    }

    /**
     * Restore (release) the message for the provided recipient.
     */
    public function dispatchRelease(Entity\Msgrcpt $messageRecipient, int $finalStatus = MessageStatus::RESTORED): void
    {
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

    public function restore(Entity\Msgrcpt $messageRecipient): void
    {
        $this->dispatchRelease($messageRecipient, MessageStatus::RESTORED);
    }

    /**
     * Mark a message and its recipient as deleted.
     */
    public function delete(Entity\Msgs $message, Entity\Msgrcpt $messageRecipient): bool
    {
        $this->msgrcptRepository->changeStatus(
            $message->getPartitionTag(),
            $message->getMailIdAsString(),
            MessageStatus::DELETED,
            $messageRecipient->getRid()->getId(),
        );

        return true;
    }

    /**
     * Add the message's sender to a wblist and release (if $wb is 'W') the message.
     *
     * @param Entity\Msgrcpt[] $messageRecipients
     * @param 'W'|'B' $wb
     *
     * @throws NotFoundHttpException
     */
    private function msgsToWblist(
        Entity\Msgs $message,
        array $messageRecipients,
        string $wb,
        int $type = Entity\Wblist::WBLIST_TYPE_USER,
    ): bool {
        $messageStatus = $wb === 'W' ? MessageStatus::AUTHORIZED : MessageStatus::BANNED;

        //check if sender exists in the database
        $emailSender = $message->getSid()->getEmailClear();
        if ($emailSender === null) {
            return false;
        }
        //check from_addr email. If it's different from $mailaddrSender we will use it from wblist

        $fromAddr = $message->getFromMimeAddress()?->getAddress();

        $emailSenderToWb = ($fromAddr && $emailSender != $fromAddr) ? $fromAddr : $emailSender;

        $mailaddrSender = $this->em->getRepository(Entity\Mailaddr::class)->findOneBy(['email' => $emailSenderToWb]);

        //if not we create email in Mailaddr
        if (!$mailaddrSender) {
            $mailaddrSender = new Entity\Mailaddr();
            $mailaddrSender->setEmail($emailSenderToWb);
            $mailaddrSender->setPriority(6);
            $this->em->persist($mailaddrSender);
        }

        $userAndAliases = [];
        foreach ($messageRecipients as $messageRecipient) {
            $emailReceipt = stream_get_contents($messageRecipient->getRid()->getEmail(), -1, 0);
            $mainUser = $this->em->getRepository(Entity\User::class)->findOneBy(['email' => $emailReceipt]);

            // if adress in an alias we get the target mail
            if ($mainUser && $mainUser->getOriginalUser()) {
                $mainUser = $mainUser->getOriginalUser();
            }

            // we check if aliases exist
            if ($mainUser) {
                $userAndAliases = $this->em->getRepository(Entity\User::class)->findBy([
                    'originalUser' => $mainUser->getId(),
                ]);
                array_unshift($userAndAliases, $mainUser);
            }

            foreach ($userAndAliases as $user) {
                //add white liste
                //        $sid = $mailaddrSender->getId();

                //if the message is authorized we release all message of the same sender
                //create Wblist with value White and user (User Object) and mailSender (Mailaddr Object)
                $wblist = $this->em->getRepository(Entity\Wblist::class)->findOneBy([
                    'sid' => $mailaddrSender,
                    'rid' => $user,
                ]);
                if (!$wblist) {
                    $wblist = new Entity\Wblist($user, $mailaddrSender);
                }
                $wblist->setWb($wb);
                $wblist->setType($type);
                $wblist->setPriority(Entity\Wblist::WBLIST_PRIORITY_USER);
                $this->em->persist($wblist);



                //get all messages from email Sender and send all message for one user
                $msgsRelease = $this->em->getRepository(Entity\Msgs::class)
                                        ->getMessagesToRelease($emailSender, $user);
                foreach ($msgsRelease as $msgRelease) {
                    $oneMsgRcpt = $this->em->getRepository(Entity\Msgrcpt::class)->findOneBy([
                        'partitionTag' => $msgRelease['partition_tag'],
                        'mailId' => $msgRelease['mail_id'],
                        'rid' => $msgRelease['rid']
                    ]);
                    //if W we deliver blokec messages if it has been released yet
                    if ($wb == 'W' && !$oneMsgRcpt->getAmavisOutput()) {
                        $this->dispatchRelease($oneMsgRcpt, MessageStatus::AUTHORIZED);
                    } else {
                        $oneMsgRcpt->setStatus($messageStatus);
                        $this->em->persist($oneMsgRcpt);
                    }
                }
            }
        }

        $message->setStatus($messageStatus);
        $this->em->persist($message);
        $this->em->flush();
        return true;
    }

    /**
     * Return a secure token containing the id of the user and of the message.
     * The token is valid for 7 days.
     */
    public function getReleaseToken(Entity\Msgs $message, Entity\User $user): string
    {
        $data = $user->getId() . '%%%' . $message->getMailIdAsString();
        return $this->cryptEncryptService->encrypt($data, lifetime: 7 * 24 * 3600);
    }

    /**
     * Extract the data from a token (i.e. a user and a mail id).
     *
     * If the token is invalid or expired, it returns null.
     *
     * @return ?array{Entity\User, ?string}
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
