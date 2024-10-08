<?php
// src/Command/CheckOutMsgsCommand.php

namespace App\Command;

use App\Entity\OutMsg;
use App\Message\CreateAlertMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:check-out-msgs',
    description: 'Check for new out_msgs with content "V" and dispatch alert messages.',
)]
class CheckOutMsgsCommand extends Command
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
        $output->writeln('Starting check-out-msgs command...');

        $outMsgs = $this->entityManager->getRepository(OutMsg::class)->createQueryBuilder('o')
            ->where('o.content = :content')
            ->andWhere('o.processed = :processed')
            ->setParameter('content', 'V')
            ->setParameter('processed', false)
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

            // Detach the entity to avoid conflicts
            $this->entityManager->detach($outMsg);

            $output->writeln('Dispatching message for mail ID: ' . $mailId);
            $this->messageBus->dispatch(new CreateAlertMessage($mailId));
        }

        $output->writeln('Finished check-out-msgs command.');

        sleep(5);

        return Command::SUCCESS;
    }
}