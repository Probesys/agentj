<?php

namespace App\Command;

use App\Amavis\MessageStatus;
use App\Repository\MsgrcptSearchRepository;
use App\Repository\UserRepository;
use App\Service\MessageService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;

#[AsCommand(
    name: 'agentj:auto-release-message',
    description: 'Release untreated messages for users with bypass_human_auth enabled',
)]
class AmavisAutoReleaseCommand extends Command
{
    private int $batchSize = 500;

    public function __construct(
        private MsgrcptSearchRepository $msgrcptSearchRepository,
        private UserRepository $userRepository,
        private MessageService $messageService,
        private LockFactory $lockFactory,
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

        $users = $this->userRepository->findAllWithoutHumanAuthentication();

        foreach ($users as $user) {
            $searchQuery = $this->msgrcptSearchRepository->getSearchQuery(
                $user,
                MessageStatus::UNTREATED
            );

            $messageRecipients = $searchQuery->setMaxResults($this->batchSize)->getResult();

            foreach ($messageRecipients as $messageRecipient) {
                if (!$messageRecipient->isAmavisReleaseOngoing()) {
                    $this->messageService->dispatchRelease($messageRecipient);
                }
            }
        }

        $lock->release();

        return Command::SUCCESS;
    }
}
