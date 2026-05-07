<?php

namespace App\Command;

use App\Amavis\MessageStatus;
use App\Repository\MsgsRepository;
use App\Service\MessageService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'agentj:messages:mark-as',
    description: 'Mark a message as spam or ham.',
)]
class MessageMarkAsCommand
{
    public function __construct(
        private MsgsRepository $messageRepository,
        private MessageService $messageService,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[Argument('The status to apply to the message (spam or ham).', suggestedValues: ['spam', 'ham'])]
        string $status,
        #[Argument('The mail id of the message to mark.')]
        string $mailId,
        #[Option('The partition tag of the message to mark.')]
        int $partitionTag = 0,
    ): int {
        if ($status !== 'spam' && $status !== 'ham') {
            $output->writeln('Status must either be "ham" or "spam".');
            return Command::FAILURE;
        }

        $message = $this->messageRepository->findOneByMailId($partitionTag, $mailId);

        if (!$message) {
            $output->writeln("Message with mail id {$mailId} (partition tag {$partitionTag}) does not exists.");
            return Command::FAILURE;
        }

        $output->write("Marking the message as {$status}... ");

        if ($status === 'spam') {
            $result = $this->messageService->markMessageAsSpam($message);
        } else {
            $result = $this->messageService->markMessageAsHam($message);
        }

        if ($result) {
            $output->writeln('ok');
            return Command::SUCCESS;
        } else {
            $output->writeln('error');
            return Command::FAILURE;
        }
    }
}
