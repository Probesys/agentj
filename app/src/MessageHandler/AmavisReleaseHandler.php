<?php

namespace App\MessageHandler;

use App\Amavis\MessageStatus;
use App\Message;
use App\Repository\MessageRecipientRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
final class AmavisReleaseHandler
{
    public function __construct(
        #[Autowire(param: 'app.amavisd-release')]
        private string $amavisdReleaseCommand,
        private MessageRecipientRepository $messageRecipientRepository,
        private LoggerInterface $logger,
        private LockFactory $lockFactory,
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

        if ($messageRecipient->getMessage()->getQuarLoc() === null) {
            $this->logger->error('Mail cannot be released  : invalid quartloc', [
                'mailId' => $amavisRelease->getMailId(),
                'partitionTag' => $amavisRelease->getPartitionTag(),
                'rseqnum' => $amavisRelease->getRseqnum(),
            ]);
            return;
        }

        if ($messageRecipient->getMessage()->getSecretId() === null) {
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

        $lockName = (
            'amavis-release'
            . "-{$amavisRelease->getPartitionTag()}"
            . "-{$amavisRelease->getMailId()}"
            . "-{$amavisRelease->getRseqnum()}"
        );
        $lock = $this->lockFactory->createLock($lockName, ttl: 10 * 60);

        if (!$lock->acquire()) {
            $this->logger->info("Can't acquire the {$lockName} lock, the release is probably already running.");
            return;
        }

        $process = new Process([
            $this->amavisdReleaseCommand,
            $messageRecipient->getMessage()->getQuarLoc(),
            $messageRecipient->getMessage()->getSecretId(),
            $messageRecipient->getAddress()->getEmail(),
        ]);

        $messageRecipient->setAmavisOutput('');
        $process->run(
            function ($type, $buffer) use ($messageRecipient) {
                $messageRecipient->setAmavisOutput($messageRecipient->getAmavisOutput() . $buffer);
            }
        );

        // amavisd-release only reports whether it could talk to amavisd over
        // the AM.PDP socket: it exits successfully even when the downstream
        // MTA rejects the actual re-injection (e.g. a temporary "452 4.3.1
        // Insufficient system storage"). The real outcome is the SMTP-style
        // reply amavisd forwards to us in its output, so it must be checked
        // too, otherwise a failed release still gets marked as released and
        // can never be retried.
        $amavisOutput = $messageRecipient->getAmavisOutput() ?? '';
        $mtaAccepted = (bool) preg_match('/^\s*2\d\d[\s.]/', $amavisOutput);

        if (!$process->isSuccessful() || !$mtaAccepted) {
            $this->logger->error('Amavis release failed', [
                'mailId' => $messageRecipient->getMailId(),
                'partitionTag' => $messageRecipient->getPartitionTag(),
                'rseqnum' => $messageRecipient->getRseqnum(),
                'output' => $amavisOutput !== '' ? $amavisOutput : $process->getErrorOutput(),
            ]);
            $messageRecipient->setStatus(MessageStatus::ERROR);
        } else {
            $this->logger->info('Amavis successfully released mail ', [
                'mailId' => $messageRecipient->getMailId()
            ]);
            $messageRecipient->setStatus($amavisRelease->getFinalStatus());
        }

        $messageRecipient->setAmavisReleaseEndedAt(new \DateTimeImmutable());
        $this->messageRecipientRepository->save($messageRecipient);

        $lock->release();
    }
}
