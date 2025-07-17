<?php

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
    description: 'Check for new out virus messages and new limit reports and dispatch alert messages to users.',
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

        $outMsgs = $this->entityManager->getRepository(OutMsg::class)->createQueryBuilder('o')
            ->where('o.content = :content')
            ->andWhere('o.processedUser = :processedUser')
            ->setParameter('content', 'V')
            ->setParameter('processedUser', false)
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
            $this->messageBus->dispatch(new CreateAlertMessage('out_msg', $mailId, 'user'));

            $binaryMailId = hex2bin($mailId);

            // Mark the outmsg as processedUser
            $outMsgToSave = $this->entityManager->getRepository(OutMsg::class)->findOneBy(['mailId' => $binaryMailId]);
            $outMsgToSave->setProcessedUser(true);
            $this->entityManager->persist($outMsgToSave);
            $this->entityManager->flush();
        }

        $reports = $this->entityManager->getRepository(SqlLimitReport::class)->createQueryBuilder('r')
            ->select('r.id, r.mailId, r.date, r.recipientCount, r.delta, r.processedUser, COUNT(r) as reportCount')
            ->where('r.processedUser = :processedUser')
            ->setParameter('processedUser', false)
            ->groupBy('r.date')
            ->getQuery()
            ->getResult();

        $totalCount = array_reduce($reports, function ($carry, $report) {
            return $carry + $report['reportCount'];
        }, 0);

        $output->writeln('Total number of reports found: ' . $totalCount);
        $output->writeln('Number of unique report groups found: ' . count($reports));

        foreach ($reports as $report) {
            $reportDateString = $report['date']->format('Y-m-d H:i:s');

            // Find all SqlLimitReport records with the same date as $report
            $sqlLimitReports = $this->entityManager->getRepository(SqlLimitReport::class)->createQueryBuilder('r')
                ->where('r.date = :date')
                ->setParameter('date', $report['date'])
                ->getQuery()
                ->getResult();

            if (empty($sqlLimitReports)) {
                $output->writeln('No SqlLimitReport records found for date: ' . $reportDateString);
                continue;
            }

            // Mark each report as processedUser
            foreach ($sqlLimitReports as $sqlLimitReport) {
                $sqlLimitReport->setProcessedUser(true);
                $this->entityManager->persist($sqlLimitReport);
            }

            try {
                // Flush once after all updates
                $this->entityManager->flush();
                $output->writeln('Reports for date ' . $reportDateString . ' marked as processedUser.');

                $this->messageBus->dispatch(new CreateAlertMessage('sql_limit_report', $reportDateString, 'user'));
            } catch (\Exception $e) {
                $output->writeln(
                    'Failed to mark reports as processedUser for date ' . $reportDateString . ': ' . $e->getMessage()
                );
            }
        }

        $output->writeln('Finished create-alert-for-user command.');

        sleep(15);

        return Command::SUCCESS;
    }
}
