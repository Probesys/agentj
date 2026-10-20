<?php

namespace App\Command;

use App\Amavis\MessageStatus;
use App\Entity\Address;
use App\Entity\MessageRecipient;
use App\Entity\User;
use App\Message\AmavisAutoRelease;
use App\Repository\MessageRecipientRepository;
use App\Repository\SenderRuleRepository;
use App\Repository\UserRepository;
use App\Service\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'agentj:auto-release-message',
    description: 'Release untreated messages for users with bypass_human_auth enabled',
)]
class AmavisAutoReleaseCommand extends Command
{
    /** @var array<int|string, User|null> */
    private array $recipientUsersCache = [];

    /** @var array<int|string, array<string, bool>> */
    private array $authorizationsCache = [];

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

    protected function configure(): void
    {
        $this->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Number of messages read per batch', '500');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = filter_var($input->getOption('batch-size'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($batchSize === false) {
            $output->writeln('<error>The --batch-size option must be a positive integer.</error>');
            return Command::INVALID;
        }

        $lock = $this->lockFactory->createLock('msgs-auto-release', ttl: 1800);

        if (!$lock->acquire()) {
            $output->writeln("Can't acquire the msgs-auto-release lock, the command is probably already running.");
            return Command::FAILURE;
        }

        try {
            $messageRecipients = $this->messageRecipientRepository->findForAutoRelease(
                $batchSize,
                new \DateTimeImmutable(MessageRecipient::AMAVIS_RELEASE_TIMEOUT),
            );
            $this->processBatch($messageRecipients);
            $fullBatch = count($messageRecipients) === $batchSize;
        } finally {
            $lock->release();
        }

        if ($fullBatch) {
            // Queue the next batch after this one's AmavisRelease messages.
            // Continue without waiting for the scheduler, while giving the shared
            // worker a chance to deliver these emails before looking for more.
            $this->entityManager->clear();
            $this->bus->dispatch(new AmavisAutoRelease($batchSize));
        }

        return Command::SUCCESS;
    }

    /**
     * @param MessageRecipient[] $messageRecipients Recipients to process in this batch.
     */
    private function processBatch(array $messageRecipients): void
    {
        $this->recipientUsersCache = [];
        $this->authorizationsCache = [];

        foreach ($messageRecipients as $messageRecipient) {
            $recipient = $messageRecipient->getAddress();

            $recipientUser = $this->findUserRecipient($recipient);

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
                $message = $messageRecipient->getMessage();
                $fromAddress = $message->getFromMimeAddress();
                if ($fromAddress !== null) {
                    $senderIsAuthorized = $this->isSenderAuthorized($fromAddress->getAddress(), $recipient);
                }

                if (!$senderIsAuthorized) {
                    $senderEmail = $message->getSenderAddress()->getEmail();
                    $senderIsAuthorized = $this->isSenderAuthorized($senderEmail, $recipient);
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

    private function findUserRecipient(Address $recipient): ?User
    {
        $recipientId = $recipient->getId();
        if (!array_key_exists($recipientId, $this->recipientUsersCache)) {
            $this->recipientUsersCache[$recipientId] = $this->userRepository->findOneByAddressForAutoRelease(
                $recipient,
            );
        }

        return $this->recipientUsersCache[$recipientId];
    }

    /**
     * @param string $senderEmail Sender address to check.
     * @param Address $recipient Recipient to authorize the sender for.
     */
    private function isSenderAuthorized(
        string $senderEmail,
        Address $recipient,
    ): bool {
        $recipientId = $recipient->getId();
        if (!isset($this->authorizationsCache[$recipientId][$senderEmail])) {
            $isAuthorized = $this->senderRuleRepository->isSenderAuthorizedByRecipient(
                $senderEmail,
                $recipient,
            );
            $this->authorizationsCache[$recipientId][$senderEmail] = $isAuthorized;
        }

        return $this->authorizationsCache[$recipientId][$senderEmail];
    }
}
