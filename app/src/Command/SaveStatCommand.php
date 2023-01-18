<?php

namespace App\Command;

use App\Entity\DailyStat;
use App\Entity\Domain;
use App\Entity\MessageStatus;
use App\Repository\MsgsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
            name: 'agent:save-stat',
            description: 'Add a short description for your command',
    )]
class SaveStatCommand extends Command {

    private MsgsRepository $msgsRepository;
    private EntityManagerInterface $em;

    protected function configure(): void {
        $this
                ->addArgument('day', InputArgument::OPTIONAL, 'Day  (YYYYMMDD) to save stat');
    }

    public function __construct(MsgsRepository $msgsRepository, EntityManagerInterface $em) {
        parent::__construct();
        $this->msgsRepository = $msgsRepository;
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $day = $input->getArgument('day');
        $dateToSave = new \DateTime();
        $dateToSave->modify('-1 day');

        if ($day) {
            $dateToSave = \Datetime::createFromFormat('Ymd', $day);
            if (!$dateToSave) {
                $io->error('Invalid date format. Please enter a date with YYYYMMDD format');
                return Command::FAILURE;
            }
        }

        $domains = $this->em->getRepository(Domain::class)->findBy(['active' => true]);
        foreach ($domains as $domain) {
            $stat = $this->em->getRepository(DailyStat::class)->findOneBy(['date' => $dateToSave, 'domain' => $domain]);
            if (!$stat) {
                $stat = new DailyStat();
            }

            $nbUntreated = $this->msgsRepository->countByTypeAndDays(null, MessageStatus::UNTREATED, null, $dateToSave, $domain);
            $nbSpam = $this->msgsRepository->countByTypeAndDays(null, MessageStatus::SPAMMED, null, $dateToSave, $domain);
            $nbVirus = $this->msgsRepository->countByTypeAndDays(null, MessageStatus::VIRUS, null, $dateToSave, $domain);
            $nbAuthorized = $this->msgsRepository->countByTypeAndDays(null, MessageStatus::AUTHORIZED, null, $dateToSave, $domain);
            $nbBanned = $this->msgsRepository->countByTypeAndDays(null, MessageStatus::BANNED, null, $dateToSave, $domain);
            $nbDeleted = $this->msgsRepository->countByTypeAndDays(null, MessageStatus::DELETED, null, $dateToSave, $domain);
            $nbRestored = $this->msgsRepository->countByTypeAndDays(null, MessageStatus::RESTORED, null, $dateToSave, $domain);

            $stat->setDate($dateToSave);
            $stat->setDomain($domain);
            $stat->setNbUntreated(count($nbUntreated) > 0 ? $nbUntreated[0]['nb_result'] : 0);
            $stat->setNbSpam(count($nbSpam) > 0 ? $nbSpam[0]['nb_result'] : 0);
            $stat->setNbVirus(count($nbVirus) > 0 ? $nbVirus[0]['nb_result'] : 0);
            $stat->setNbAuthorized(count($nbAuthorized) > 0 ? $nbAuthorized[0]['nb_result'] : 0);
            $stat->setNbBanned(count($nbBanned) > 0 ? $nbBanned[0]['nb_result'] : 0);
            $stat->setNbDeleted(count($nbDeleted) > 0 ? $nbDeleted[0]['nb_result'] : 0);
            $stat->setNbRestored(count($nbRestored) > 0 ? $nbRestored[0]['nb_result'] : 0);
            $this->em->persist($stat);
        }


        $this->em->flush();
        $io->success(date('Y-m-d H:i:s') . '\nStat saved');

        return Command::SUCCESS;
    }

}
