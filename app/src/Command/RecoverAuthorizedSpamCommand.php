<?php

namespace App\Command;

use App\Amavis\MessageStatus;
use App\Repository\MessageRecipientRepository;
use App\Repository\SenderRuleRepository;
use App\Repository\UserRepository;
use App\Service\MessageService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;

#[AsCommand(
    name: 'agentj:recover-authorized-spam',
    description: 'Release messages incorrectly classified as spam for an authorized sender',
)]
class RecoverAuthorizedSpamCommand extends Command
{
    public function __construct(
        private MessageRecipientRepository $messageRecipientRepository,
        private UserRepository $userRepository,
        private SenderRuleRepository $senderRuleRepository,
        private MessageService $messageService,
        private LockFactory $lockFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'since',
                null,
                InputOption::VALUE_REQUIRED,
                'Only inspect messages received on or after this ISO-8601 date',
            )
            ->addOption('release', null, InputOption::VALUE_NONE, 'Release the reported messages')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Number of messages read per batch', '500');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sinceValue = $input->getOption('since');
        if (!is_string($sinceValue) || $sinceValue === '') {
            $output->writeln('<error>The --since option is required.</error>');
            return Command::INVALID;
        }

        try {
            $since = new \DateTimeImmutable($sinceValue);
        } catch (\Exception) {
            $output->writeln('<error>The --since option must contain a valid ISO-8601 date.</error>');
            return Command::INVALID;
        }

        $batchSize = filter_var($input->getOption('batch-size'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 5000],
        ]);
        if ($batchSize === false) {
            $output->writeln('<error>The --batch-size option must be between 1 and 5000.</error>');
            return Command::INVALID;
        }

        $lock = $this->lockFactory->createLock('recover-authorized-spam', ttl: 3600);
        if (!$lock->acquire()) {
            $output->writeln('<error>The recovery command is already running.</error>');
            return Command::FAILURE;
        }

        $release = (bool) $input->getOption('release');
        $until = time();
        $cursor = null;
        $inspectedCount = 0;
        $recoverableCount = 0;

        $output->writeln(
            '<comment>Messages with a retained "marked as spam" audit log are excluded.</comment>',
        );

        try {
            do {
                $messageRecipients = $this->messageRecipientRepository->findSpammedSinceAfter(
                    $since->getTimestamp(),
                    $until,
                    $cursor,
                    $batchSize,
                );

                foreach ($messageRecipients as $messageRecipient) {
                    $message = $messageRecipient->getMessage();
                    $cursor = [
                        'timeNum' => (int) $message->getTimeNum(),
                        'partitionTag' => $messageRecipient->getPartitionTag(),
                        'mailId' => $messageRecipient->getMailId(),
                        'rseqnum' => (int) $messageRecipient->getRseqnum(),
                    ];
                    ++$inspectedCount;

                    $recipientUser = $this->userRepository->findOneByAddress($messageRecipient->getAddress());
                    $senderEmail = $message->getSenderEmail();
                    if ($recipientUser === null || $senderEmail === null) {
                        continue;
                    }

                    $recipientUser = $recipientUser->getOriginalUser() ?? $recipientUser;
                    $senderIsAuthorized = $this->senderRuleRepository->isSenderAuthorizedByRecipient(
                        $senderEmail,
                        $messageRecipient->getAddress(),
                    );
                    $isSpamForAuthorizedSender = $messageRecipient->isSpamAtLevel(
                        $recipientUser->getDomain()->getAuthorizedSendersSpamLevel(),
                    );
                    if (!$senderIsAuthorized || $isSpamForAuthorizedSender) {
                        continue;
                    }

                    ++$recoverableCount;
                    if ($output->isVerbose()) {
                        $output->writeln(sprintf(
                            '%s -> %s; score %.2f; authorized threshold %.2f; mail %s/%s/%s',
                            $senderEmail,
                            $messageRecipient->getAddress()->getEmail(),
                            $messageRecipient->getBspamLevel(),
                            $recipientUser->getDomain()->getAuthorizedSendersSpamLevel(),
                            $messageRecipient->getPartitionTag(),
                            $messageRecipient->getMailId(),
                            $messageRecipient->getRseqnum(),
                        ));
                    }
                    if ($release) {
                        $this->messageService->dispatchRelease($messageRecipient, MessageStatus::AUTHORIZED);
                    }
                }

                $lock->refresh();
            } while (count($messageRecipients) === $batchSize);
        } finally {
            $lock->release();
        }

        $action = $release ? 'were queued for release' : 'would be released';
        $output->writeln(
            "Inspected {$inspectedCount} spammed message(s); {$recoverableCount} {$action}.",
        );

        return Command::SUCCESS;
    }
}
