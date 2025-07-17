<?php

namespace App\Command;

use App\Entity\DailyStat;
use App\Entity\Domain;
use App\Amavis\MessageStatus;
use App\Repository\MsgrcptSearchRepository;
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
class SaveStatCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('day', InputArgument::OPTIONAL, 'Day  (YYYYMMDD) to save stat');
    }

    public function __construct(
        private MsgrcptSearchRepository $msgrcptSearchRepository,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $day = $input->getArgument('day');
        $dateToSave = new \DateTime();
        $dateToSave->modify('-1 day')->setTime(0, 0, 0);

        if ($day) {
            $dateToSave = \Datetime::createFromFormat('Ymd', $day);
            if (!$dateToSave) {
                $io->error('Invalid date format. Please enter a date with YYYYMMDD format');
                return Command::FAILURE;
            }
        }

        $timestamp = $dateToSave->getTimestamp();

        $domains = $this->em->getRepository(Domain::class)->findBy(['active' => true]);
        foreach ($domains as $domain) {
            $stat = $this->em->getRepository(DailyStat::class)->findOneBy(['date' => $dateToSave, 'domain' => $domain]);
            if (!$stat) {
                $stat = new DailyStat();
            }

            $nbUntreated = $this->msgrcptSearchRepository->countByTypeAndDays(
                messageStatus: MessageStatus::UNTREATED,
                fromDate: $timestamp,
                domain: $domain
            );
            $nbSpam = $this->msgrcptSearchRepository->countByTypeAndDays(
                messageStatus: MessageStatus::SPAMMED,
                fromDate: $timestamp,
                domain: $domain
            );
            $nbVirus = $this->msgrcptSearchRepository->countByTypeAndDays(
                messageStatus: MessageStatus::VIRUS,
                fromDate: $timestamp,
                domain: $domain
            );
            $nbAuthorized = $this->msgrcptSearchRepository->countByTypeAndDays(
                messageStatus: MessageStatus::AUTHORIZED,
                fromDate: $timestamp,
                domain: $domain
            );
            $nbBanned = $this->msgrcptSearchRepository->countByTypeAndDays(
                messageStatus: MessageStatus::BANNED,
                fromDate: $timestamp,
                domain: $domain
            );
            $nbDeleted = $this->msgrcptSearchRepository->countByTypeAndDays(
                messageStatus: MessageStatus::DELETED,
                fromDate: $timestamp,
                domain: $domain
            );
            $nbRestored = $this->msgrcptSearchRepository->countByTypeAndDays(
                messageStatus: MessageStatus::RESTORED,
                fromDate: $timestamp,
                domain: $domain
            );

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
