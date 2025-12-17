<?php

namespace App\Command;

use App\Entity\Log;
use App\Entity\Msgs;
use App\Entity\OutMsg;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'agentj:truncate-message-since-days',
    description: 'Truncate incoming and outgoing messages older than a number of days (minimum 10, 30 by default)',
)]
class TruncateMessageCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('days');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = (int)$input->getArgument('days');
        if ($days <= 10) {
            $days = 30;
        }

        $date = (new \DateTimeImmutable())->modify("-{$days} days");
        $formattedDate = $date->format('Y-m-d');

        $output->writeln("Deleting data older than {$formattedDate}...");

        $nbDeletedIncomingMessages = $this->em->getRepository(Msgs::class)->truncateOlder($date);
        $output->writeln("Deleted {$nbDeletedIncomingMessages} incoming mails");

        $nbDeletedOutgoingMessages = $this->em->getRepository(OutMsg::class)->truncateOlder($date);
        $output->writeln("Deleted {$nbDeletedOutgoingMessages} outgoing mails");

        $nbDeletedLogs = $this->em->getRepository(Log::class)->truncateOlder($days);
        $output->writeln("Deleted {$nbDeletedLogs} logs");

        return Command::SUCCESS;
    }
}
