<?php
// src/Command/CreateAlertForUserCommand.php

namespace App\Command;

use App\Entity\OutMsg;
use App\Entity\SqlLimitReport;
use App\Message\CreateAlertMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:create-alert-for-user',
    description: 'Check for new out_msgs with content "V" and new sql_limit_report entries and dispatch alert messages to users.',
)]
class CreateAlertForUserCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $messageBus)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting create-alert-for-user command...');

        // No alert for a user when he send a message with virus, only admin

        $reports = $this->entityManager->getRepository(SqlLimitReport::class)->createQueryBuilder('r')
            ->select('r.id, r.date, r.recipientCount, r.delta, r.processed_user, COUNT(r) as reportCount')
            ->where('r.processed_user = :processed_user')
            ->setParameter('processed_user', false)
            ->groupBy('r.date')
            ->getQuery()
            ->getResult();

        $totalCount = array_reduce($reports, function($carry, $report) {
            return $carry + $report['reportCount'];
        }, 0);

        $output->writeln('Total number of reports found: ' . $totalCount);
        $output->writeln('Number of unique report groups found: ' . count($reports));

        foreach ($reports as $report) {
            $reportDateString = $report['date']->format('Y-m-d H:i:s');
            $this->messageBus->dispatch(new CreateAlertMessage('sql_limit_report', $reportDateString, 'user'));
        }

        $output->writeln('Finished create-alert-for-user command.');

        sleep(15);

        return Command::SUCCESS;
    }
}