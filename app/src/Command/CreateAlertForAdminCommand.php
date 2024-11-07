<?php
// src/Command/CreateAlertForAdminCommand.php

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
    name: 'app:create-alert-for-admin',
    description: 'Check for new out_msgs with content "V" and new sql_limit_report entries and dispatch alert messages to admins.',
)]
class CreateAlertForAdminCommand extends Command
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
        $output->writeln('Starting create-alert-for-admin command...');

        $outMsgs = $this->entityManager->getRepository(OutMsg::class)->createQueryBuilder('o')
            ->where('o.content = :content')
            ->andWhere('o.processed_admin = :processed_admin')
            ->setParameter('content', 'V')
            ->setParameter('processed_admin', false)
            ->getQuery()
            ->getResult();

        $output->writeln('Number of messages found: ' . count($outMsgs));

        foreach ($outMsgs as $outMsg) {
            $mailId = $outMsg->getMailId();
            $output->writeln('Retrieved mail ID: ' . $mailId);

            // Add a check to ensure mailId is not empty
            if (empty($mailId)) {
                $output->writeln('Error: mailId is empty for OutMsg with ID: ' . $outMsg->getId());
                continue;
            }

            $output->writeln('Dispatching message for mail ID: ' . $mailId);
            $this->messageBus->dispatch(new CreateAlertMessage('out_msg', $mailId, 'admin'));

            $binaryMailId = hex2bin($mailId);

            // Mark the outmsg as processed_admin
            $outMsgToSave = $this->entityManager->getRepository(OutMsg::class)->findOneBy(['mail_id' => $binaryMailId]);
            $outMsgToSave->setProcessedAdmin(true);
            $this->entityManager->persist($outMsgToSave);
            $this->entityManager->flush();
        }

        $reports = $this->entityManager->getRepository(SqlLimitReport::class)->createQueryBuilder('r')
            ->select('r.id, r.mail_id, r.date, r.recipientCount, r.delta, r.processed_admin, COUNT(r) as reportCount')
            ->where('r.processed_admin = :processed_admin')
            ->setParameter('processed_admin', false)
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

            // Find all SqlLimitReport records with the same datetime as $report
            $queryBuilder = $this->entityManager->createQueryBuilder()
                ->add('select', 'r')
                ->add('from', 'App\Entity\SqlLimitReport r')
                ->add('where', 'r.date = :date');

            $sqlLimitReports = $queryBuilder->setParameter('date', $report['date'])
                ->getQuery()
                ->getResult();

            if (empty($sqlLimitReports)) {
                $output->writeln('No SqlLimitReport records found for date: ' . $reportDateString);
                continue;
            }

            // Mark each report as processed_admin
            foreach ($sqlLimitReports as $sqlLimitReport) {
                $sqlLimitReport->setProcessedAdmin(true);
                $this->entityManager->persist($sqlLimitReport);
            }

            try {
                // Flush once after all updates
                $this->entityManager->flush();
                $output->writeln('Reports for date ' . $reportDateString . ' marked as processed_admin.');

                $this->messageBus->dispatch(new CreateAlertMessage('sql_limit_report', $reportDateString, 'admin'));
            } catch (\Exception $e) {
                $output->writeln('Failed to mark reports as processed_admin for date ' . $reportDateString . ': ' . $e->getMessage());
            }
        }

        $output->writeln('Finished create-alert-for-admin command.');

        sleep(60);

        return Command::SUCCESS;
    }
}