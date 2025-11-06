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

        $now = time();
        $start = strtotime('-' . $days . ' day', $now);
        if ($start === false) {
            return Command::FAILURE;
        }

        // Truncate incoming messages
        $deletedIncomingStats = $this->em->getRepository(Msgs::class)->truncateMessageOlder($start);

        $message = date('Y-m-d H:i:s');
        $message .= "\tdelete incoming mail entries older than " . date('Y-m-d', $start);
        $message .= "\t{$deletedIncomingStats['nbDeletedMsgs']} in msgs";
        $message .= "\t{$deletedIncomingStats['nbDeletedQuarantine']} in quarantine";
        $output->writeln($message);

        // Truncate outgoing messages
        $deletedOutgoingStats = $this->em->getRepository(OutMsg::class)->truncateMessageOlder($start);

        $message = date('Y-m-d H:i:s');
        $message .= "\tdelete outgoing mail entries older than " . date('Y-m-d', $start);
        $message .= "\t{$deletedOutgoingStats['nbDeletedMsgs']} in out_msgs";
        $message .= "\t{$deletedOutgoingStats['nbDeletedQuarantine']} in out_quarantine";
        $output->writeln($message);

        // Truncate logs
        $nbDeletedlogs = $this->em->getRepository(Log::class)->truncateOlder($days);
        $message = date('Y-m-d H:i:s');
        $message .= "\tdelete log entries older than " . date('Y-m-d', $start);
        $message .= "\t{$nbDeletedlogs} deleted";
        $output->writeln($message);
        return Command::SUCCESS;
    }
}
