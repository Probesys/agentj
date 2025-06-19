<?php

namespace App\Command;

use App\Entity\Log;
use App\Entity\Msgs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'agentj:truncate-message-since-days',
    description: 'Truncate tables msgs and msgrcpt since X days in argument, X more than 10, by default it is 30 days',
)]
class TruncateMessageCommand extends Command
{

    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure():void
    {
        $this->addArgument('days');
    }

    protected function execute(InputInterface $input, OutputInterface $output):int
    {
        $days = (int)$input->getArgument('days');
      //default value is 30 days
        if ($days <= 10) {
            $days = 30;
        }

        $now = time();
        $start = strtotime('-' . $days . ' day', $now);
        if ($start === false) {
            return Command::FAILURE;
        }

        $userMsgsBlocked = $this->em->getRepository(Msgs::class)->truncateMessageOlder($start);
        $output->writeln(date('Y-m-d H:i:s') . "\tdelete mail entries older than " . date('Y-m-d', $start) . "\t" . $userMsgsBlocked['nbDeletedMsgs'] . ' in msgs' . "\t" . $userMsgsBlocked['nbDeletedMsgrcpt'] . ' in msgrcpt'  . "\t" . $userMsgsBlocked['nbDeletedQuarantine'] . ' in quarantine');
        $nbDeletedlogs = $this->em->getRepository(Log::class)->truncateOlder($days);
        $output->writeln(date('Y-m-d H:i:s') . "\tdelete log entries older than " . date('Y-m-d', $start) . "\t" . $nbDeletedlogs . ' deleted');
        return Command::SUCCESS;
    }
}
