<?php

namespace App\Service;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Repository\MsgrcptRepository;

class MessageService
{
    public function __construct(
        private EntityManagerInterface $em,
        #[Autowire(param: 'app.amavisd-release')]
        private string $amavisdReleaseCommand,
        private MsgrcptRepository $msgrcptRepository,
    ) {
    }

    /**
     * Authorize the message's sender and release the message.
     *
     * @param Entity\Msgrcpt[] $messageRecipients
     *
     * @throws ProcessFailedException
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
     *
     * @throws ProcessFailedException
     */
    public function restore(Entity\Msgs $message, Entity\Msgrcpt $messageRecipient): bool
    {
        if ($message->getQuarLoc() && $message->getSecretId()) {
            $mailRcpt = stream_get_contents($messageRecipient->getRid()->getEmail(), -1, 0);
            $process = new Process([
                $this->amavisdReleaseCommand,
                stream_get_contents($message->getQuarLoc(), -1, 0),
                stream_get_contents($message->getSecretId(), -1, 0),
                $mailRcpt,
            ]);
            $process->run(
                function ($type, $buffer) use ($messageRecipient) {
                    $messageRecipient->setAmavisOutput($buffer);
                }
            );

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $msgStatus = $this->em->getRepository(Entity\MessageStatus::class)->find(Entity\MessageStatus::RESTORED);
            $message->setStatus($msgStatus);
            $this->em->persist($message);

            $messageRecipient->setStatus($msgStatus);
            $this->em->persist($messageRecipient);

            $this->em->flush();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Mark a message and its recipient as deleted.
     */
    public function delete(Entity\Msgs $message, Entity\Msgrcpt $messageRecipient): bool
    {
        $this->msgrcptRepository->changeStatus(
            $message->getPartitionTag(),
            $message->getMailIdAsString(),
            Entity\MessageStatus::DELETED,
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
     * @throws ProcessFailedException
     */
    private function msgsToWblist(
        Entity\Msgs $message,
        array $messageRecipients,
        string $wb,
        int $type = Entity\Wblist::WBLIST_TYPE_USER,
    ): bool {
        $status = $wb === 'W' ? Entity\MessageStatus::AUTHORIZED : Entity\MessageStatus::BANNED;

        //check if sender exists in the database
        $emailSender = stream_get_contents($message->getSid()->getEmail(), -1, 0);
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
                $userAndAliases = $this->em->getRepository(Entity\User::class)->findBy(['originalUser' => $mainUser->getId()]);
                array_unshift($userAndAliases, $mainUser);
            }

            foreach ($userAndAliases as $user) {
                //add white liste
                //        $sid = $mailaddrSender->getId();
                $rid = $user->getId();
                //if the message is authorized we release all message of the same sender
                //create Wblist with value White and user (User Object) and mailSender (Mailaddr Object)
                $wblist = $this->em->getRepository(Entity\Wblist::class)->findOneBy(['sid' => $mailaddrSender, 'rid' => $user]);
                if (!$wblist) {
                    $wblist = new Entity\Wblist($user, $mailaddrSender);
                }
                $wblist->setWb($wb);
                $wblist->setType($type);
                $wblist->setPriority(Entity\Wblist::WBLIST_PRIORITY_USER);
                $this->em->persist($wblist);

                $messageStatus = $this->em->getRepository(Entity\MessageStatus::class)->find($status);

                //get all messages from email Sender and send all message for one user
                $msgsRelease = $this->em->getRepository(Entity\Msgs::class)->getAllMessageRecipient($emailSender, $user);
                foreach ($msgsRelease as $msgRelease) {
                    if (isset($msgRelease['quar_loc']) && isset($msgRelease['secret_id'])) {
                        $oneMsgRcpt = $this->em->getRepository(Entity\Msgrcpt::class)->findOneBy(['partitionTag' => $msgRelease['partition_tag'], 'mailId' => $msgRelease['mail_id'], 'rid' => $msgRelease['rid']]);
                        //if W we deliver blokec messages if it has been released yet
                        if ($wb == 'W' && !$oneMsgRcpt->getAmavisOutput()) {
                            $process = new Process([
                                $this->amavisdReleaseCommand,
                                $msgRelease['quar_loc'],
                                $msgRelease['secret_id'],
                                $msgRelease['recept_mail'],
                            ]);
                            $process->getCommandLine();
                            $process->run(
                                function ($type, $buffer) use ($oneMsgRcpt) {
                                    $oneMsgRcpt->setAmavisOutput($buffer);
                                }
                            );
                            if (!$process->isSuccessful()) {
                                throw new ProcessFailedException($process);
                            }
                        }

                        $oneMsgRcpt->setStatus($messageStatus);
                        $this->em->persist($oneMsgRcpt);
                        $this->em->flush();
                    }
                }
            }
            $messageStatus = $this->em->getRepository(Entity\MessageStatus::class)->find($status);
            $messageRecipient->setStatus($messageStatus);
            $this->em->persist($message);
            $this->em->flush();

            return true;
        }

        return false;
    }
}
