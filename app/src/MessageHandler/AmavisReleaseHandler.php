<?php

namespace App\MessageHandler;

use App\Amavis\MessageStatus;
use App\Message;
use App\Repository\MsgrcptRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
final class AmavisReleaseHandler
{
    public function __construct(
        #[Autowire(param: 'app.amavisd-release')]
        private string $amavisdReleaseCommand,
        private MsgrcptRepository $messageRecipientRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Message\AmavisRelease $amavisRelease): void
    {
        $messageRecipient = $this->messageRecipientRepository->findOneBy([
            'mailId' => $amavisRelease->getMailId(),
            'partitionTag' => $amavisRelease->getPartitionTag(),
            'rseqnum' => $amavisRelease->getRseqnum(),
        ]);

        if (!$messageRecipient) {
            $this->logger->error('Mail cannot be found.', [
                'mailId' => $amavisRelease->getMailId(),
                'partitionTag' => $amavisRelease->getPartitionTag(),
                'rseqnum' => $amavisRelease->getRseqnum(),
            ]);
            return;
        }

        if ($messageRecipient->getMsgs()->getQuarLoc() === null) {
            $this->logger->error('Mail cannot be released  : invalid quartloc', [
                'mailId' => $amavisRelease->getMailId(),
                'partitionTag' => $amavisRelease->getPartitionTag(),
                'rseqnum' => $amavisRelease->getRseqnum(),
            ]);
            return;
        }

        if ($messageRecipient->getMsgs()->getSecretId() === null) {
            $this->logger->error('Mail cannot be released  : invalid secretid', [
                'mailId' => $amavisRelease->getMailId(),
                'partitionTag' => $amavisRelease->getPartitionTag(),
                'rseqnum' => $amavisRelease->getRseqnum(),
            ]);
            return;
        }


        if ($messageRecipient->isAlreadyReleased()) {
            $this->logger->error('Mail cannot be released as it is already authorized or restored.', [
                'mailId' => $amavisRelease->getMailId(),
                'partitionTag' => $amavisRelease->getPartitionTag(),
                'rseqnum' => $amavisRelease->getRseqnum(),
                'status' => $messageRecipient->getStatus(),
            ]);
            return;
        }

        $process = new Process([
            $this->amavisdReleaseCommand,
            $messageRecipient->getMsgs()->getQuarLoc(),
            $messageRecipient->getMsgs()->getSecretId(),
            $messageRecipient->getRid()->getEmailClear(),
        ]);

        $process->run(
            function ($type, $buffer) use ($messageRecipient) {
                $messageRecipient->setAmavisOutput($buffer);
            }
        );

        if (!$process->isSuccessful()) {
            $this->logger->error('Amavis release failed', [
                'mailId' => $messageRecipient->getMailId(),
                'partitionTag' => $messageRecipient->getPartitionTag(),
                'rseqnum' => $messageRecipient->getRseqnum(),
                'output' => $process->getErrorOutput(),
            ]);
            $messageRecipient->setStatus(MessageStatus::ERROR);
        } else {
            $messageRecipient->setStatus($amavisRelease->getFinalStatus());
        }

        $messageRecipient->setAmavisReleaseEndedAt(new \DateTimeImmutable());
        $this->messageRecipientRepository->save($messageRecipient);
    }
}
