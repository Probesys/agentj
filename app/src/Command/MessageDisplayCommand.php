<?php

namespace App\Command;

use App\Repository\MsgsRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'agentj:messages:display',
    description: 'Display a message on the standard output.',
)]
class MessageDisplayCommand
{
    public function __construct(
        private MsgsRepository $messageRepository,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[Argument('The mail id of the message to display.')]
        string $mailId,
        #[Option('The partition tag of the message to display.')]
        int $partitionTag = 0,
    ): int {
        $message = $this->messageRepository->findOneByMailId($partitionTag, $mailId);

        if (!$message) {
            $output->writeln("Message with mail id {$mailId} (partition tag {$partitionTag}) does not exists.");
            return Command::FAILURE;
        }

        $content = $message->getQuarantineContent();
        $output->writeln($content);

        return Command::SUCCESS;
    }
}
