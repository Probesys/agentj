<?php

namespace App\Command;

use App\Amavis\MessageStatus;
use App\Repository\MsgrcptRepository;
use App\Repository\MsgrcptSearchRepository;
use App\Repository\UserRepository;
use App\Repository\WblistRepository;
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
        private MsgrcptRepository $messageRecipientRepository,
        private MsgrcptSearchRepository $msgrcptSearchRepository,
        private UserRepository $userRepository,
        private WblistRepository $wblistRepository,
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

        $unreleasedSearchQuery = $this->msgrcptSearchRepository->getSearchQuery(
            null,
            messageStatus: MessageStatus::UNRELEASED,
        );
        $unreleasedSearchQuery->setMaxResults($this->batchSize);

        $messageRecipients = $unreleasedSearchQuery->getResult();

        foreach ($messageRecipients as $messageRecipient) {
            $recipient = $messageRecipient->getRid();
            $sender = $messageRecipient->getMsgs()->getSid();

            $recipientUser = $this->userRepository->findOneByMailAddress($recipient);
            $recipientDomain = $recipientUser->getDomain();

            $senderIsAuthorized = $this->wblistRepository->isSenderAuthorizedByRecipient($sender, $recipient);
            $humanAuthIsDisabled = $recipientUser->getBypassHumanAuth();

            $spamLevel = $recipientDomain->getLevel();
            $authorizedSendersSpamLevel = $recipientDomain->getAuthorizedSendersSpamLevel();
            $isSpam = $messageRecipient->isSpamAtLevel($spamLevel);
            $isAuthorizedSendersSpam = $messageRecipient->isSpamAtLevel($authorizedSendersSpamLevel);

            if ($senderIsAuthorized && !$isAuthorizedSendersSpam) {
                $this->messageService->dispatchRelease($messageRecipient, MessageStatus::AUTHORIZED);
            } elseif ($humanAuthIsDisabled && !$isSpam) {
                $this->messageService->dispatchRelease($messageRecipient, MessageStatus::RESTORED);
            } elseif (!$isSpam) {
                $messageRecipient->setStatus(MessageStatus::UNTREATED);
                $this->messageRecipientRepository->save($messageRecipient);
            } else {
                $messageRecipient->setStatus(MessageStatus::SPAMMED);
                $this->messageRecipientRepository->save($messageRecipient);
            }
        }

        $lock->release();

        return Command::SUCCESS;
    }
}
