<?php

namespace App\Command;

use App\Amavis\MessageStatus;
use App\Entity\Address;
use App\Entity\MessageRecipient;
use App\Message\AmavisAutoRelease;
use App\Repository\MessageRecipientRepository;
use App\Repository\SenderRuleRepository;
use App\Repository\UserRepository;
use App\Service\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'agentj:auto-release-message',
    description: 'Release untreated messages for users with bypass_human_auth enabled',
)]
class AmavisAutoReleaseCommand extends Command
{
    private int $batchSize = 500;

    public function __construct(
        private MessageRecipientRepository $messageRecipientRepository,
        private UserRepository $userRepository,
        private SenderRuleRepository $senderRuleRepository,
        private MessageService $messageService,
        private LockFactory $lockFactory,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
    ) {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $lock = $this->lockFactory->createLock('msgs-auto-release', ttl: 1800);

        if (!$lock->acquire()) {
            $output->writeln("Can't acquire the msgs-auto-release lock, the command is probably already running.");
            return Command::FAILURE;
        }

        try {
            $messageRecipients = $this->messageRecipientRepository->findForAutoRelease(
                $this->batchSize,
                new \DateTimeImmutable('-10 minutes'),
            );
            $this->processBatch($messageRecipients);
            $fullBatch = count($messageRecipients) === $this->batchSize;
        } finally {
            $lock->release();
        }

        if ($fullBatch) {
            // Queue the next batch after this one's AmavisRelease messages.
            // The shared worker can deliver these emails before looking for more.
            $this->entityManager->clear();
            $this->bus->dispatch(new AmavisAutoRelease());
        }

        return Command::SUCCESS;
    }

    /**
     * @param MessageRecipient[] $messageRecipients Recipients to process in this batch.
     */
    private function processBatch(array $messageRecipients): void
    {
        $recipientUsers = [];
        $authorizedSenders = [];

        foreach ($messageRecipients as $messageRecipient) {
            $recipient = $messageRecipient->getAddress();

            $recipientId = $recipient->getId();
            if (!array_key_exists($recipientId, $recipientUsers)) {
                $recipientUsers[$recipientId] = $this->userRepository->findForAutoRelease($recipient);
            }
            $recipientUser = $recipientUsers[$recipientId];

            $recipientOriginalUser = $recipientUser->getOriginalUser();
            if ($recipientOriginalUser) {
                $recipientUser = $recipientOriginalUser;
            }

            $recipientDomain = $recipientUser->getDomain();
            $humanAuthIsDisabled = !$recipientUser->isHumanAuthenticationEnabled();

            $spamLevel = $recipientDomain->getLevel();
            $authorizedSendersSpamLevel = $recipientDomain->getAuthorizedSendersSpamLevel();
            $isSpam = $messageRecipient->isSpamAtLevel($spamLevel);
            $isAuthorizedSendersSpam = $messageRecipient->isSpamAtLevel($authorizedSendersSpamLevel);

            $senderIsAuthorized = false;
            if (!$isAuthorizedSendersSpam) {
                $senderEmail = $messageRecipient->getMessage()->getSenderAddress()->getEmail();
                $senderIsAuthorized = $this->isSenderAuthorized($senderEmail, $recipient, $authorizedSenders);

                if (!$senderIsAuthorized) {
                    $fromAddress = $messageRecipient->getMessage()->getFromMimeAddress();

                    if ($fromAddress !== null) {
                        $senderIsAuthorized = $this->isSenderAuthorized(
                            $fromAddress->getAddress(),
                            $recipient,
                            $authorizedSenders,
                        );
                    }
                }
            }

            if ($senderIsAuthorized) {
                $this->messageService->dispatchRelease($messageRecipient, MessageStatus::AUTHORIZED);
            } elseif ($humanAuthIsDisabled && !$isSpam) {
                $this->messageService->dispatchRelease($messageRecipient, MessageStatus::RESTORED);
            } elseif (!$isSpam) {
                $messageRecipient->setStatus(MessageStatus::UNTREATED);
                $this->messageRecipientRepository->save($messageRecipient, flush: false);
            } else {
                $messageRecipient->setStatus(MessageStatus::SPAMMED);
                $this->messageRecipientRepository->save($messageRecipient, flush: false);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param string $senderEmail Sender address to check.
     * @param Address $recipient Recipient to authorize the sender for.
     * @param array<int|string, array<string, bool>> $authorizedSenders Decisions cached for this batch.
     */
    private function isSenderAuthorized(
        string $senderEmail,
        Address $recipient,
        array &$authorizedSenders,
    ): bool {
        $recipientId = $recipient->getId();
        if (!isset($authorizedSenders[$recipientId][$senderEmail])) {
            $isAuthorized = $this->senderRuleRepository->isSenderAuthorizedForAutoRelease(
                $senderEmail,
                $recipient,
            );
            $authorizedSenders[$recipientId][$senderEmail] = $isAuthorized;
        }

        return $authorizedSenders[$recipientId][$senderEmail];
    }
}
