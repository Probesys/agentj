<?php

namespace App\Command;

use App\Entity\Log;
use App\Entity\Msgs;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TruncateMessageCommand extends Command
{

    protected static $defaultName = 'agentj:truncate-message-since-days';

    protected function configure()
    {
        $this->setDescription('Truncate tables msgs and msgrcpt since X days in argument, X more than 10, by default it is 30 days ');
        $this->addArgument('days');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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
