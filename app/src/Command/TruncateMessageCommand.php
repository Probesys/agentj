<?php

namespace App\Command;

use App\Entity\Log;
use App\Entity\Msgs;
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


    protected function configure():void
    {
        $this->addArgument('days');
    }

    protected function execute(InputInterface $input, OutputInterface $output):int
    {
        $em = $this->getApplication()->getKernel()->getContainer()->get('doctrine')->getManager();

        $days = (int)$input->getArgument('days');
      //default value is 30 days
        if ($days <= 10) {
            $days = 30;
        }
        
        $now = time();
        $start = strtotime('-' . $days . ' day', $now);
        $userMsgsBlocked = $em->getRepository(Msgs::class)->truncateMessageOlder($start);
        $output->writeln(date('Y-m-d H:i:s') . "\tdelete mail entries older than " . date('Y-m-d', $start) . "\t" . $userMsgsBlocked['nbDeletedMsgs'] . ' in msgs' . "\t" . $userMsgsBlocked['nbDeletedMsgrcpt'] . ' in msgrcpt'  . "\t" . $userMsgsBlocked['nbDeletedQuantaine'] . ' in quarantine');
        $nbDeletedlogs = $em->getRepository(Log::class)->truncateOlder($days);
        $output->writeln(date('Y-m-d H:i:s') . "\tdelete log entries older than " . date('Y-m-d', $start) . "\t" . $nbDeletedlogs . ' deleted');
        return Command::SUCCESS;
    }
}
