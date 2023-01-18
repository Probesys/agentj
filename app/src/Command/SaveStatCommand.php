<?php

namespace App\Command;

use App\Entity\DailyStat;
use App\Entity\MessageStatus;
use App\Repository\MsgsRepository;
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

    private $msgsRepository;
    
    protected function configure(): void {
        $this
                ->addArgument('day', InputArgument::OPTIONAL, 'Day  (YYYYMMDD) to save stat')
        ;
    }
    
    public function __construct(MsgsRepository $msgsRepository) {
        parent::__construct();
        $this->msgsRepository = $msgsRepository;
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $day = $input->getArgument('day');
        $dateToSave = new \DateTime();

        if ($day) {
            $dateToSave = \Datetime::createFromFormat('Ymd', $day);
            if (!$dateToSave) {
                $io->error('Invalid date format. Please enter a date with YYYYMMDD format');
                return Command::FAILURE;
            }
        }

        $stat = new DailyStat();
        $nbUntretaed = $this->msgsRepository->countByTypeAndDays(null, MessageStatus::UNTREATED, null, $dateToSave);
        dd($nbUntretaed);
        $stat->setDate($dateToSave);

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }

}
