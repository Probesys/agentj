<?php

namespace App\Command;

use App\Repository\ReportDeliveryRepository;
use App\Repository\UserRepository;
use App\Service\ReportQueue;
use App\Service\ReportSchedule;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;

#[AsCommand(
    name: 'agentj:send-report-mail|agentj:report-send-mail',
    description: 'Queue report emails for their scheduled delivery',
)]
class SendReportMailCommand extends Command
{
    use LockableTrait;

    public function __construct(
        private UserRepository $users,
        private ReportDeliveryRepository $deliveries,
        private ReportQueue $queue,
        private ReportSchedule $schedule,
        LockFactory $lockFactory,
    ) {
        parent::__construct();
        $this->lockFactory = $lockFactory;
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Ignore report schedules and weekend restrictions');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!$this->lock()) {
            $io->note('Report scheduling is already running. Due reports will be picked up on the next run.');
            return Command::SUCCESS;
        }

        try {
            $force = (bool) $input->getOption('force');
            if ($force) {
                $io->note('Manual sending ignores schedules and weekends; disabled or pending reports are skipped.');
            }

            $now = $this->schedule->now();
            $queued = 0;
            foreach ($this->users->findUsersForReport($now, $force) as $user) {
                if ($force && $this->deliveries->hasPending($user)) {
                    $io->note(sprintf(
                        'Report already pending for %s; no additional report queued.',
                        $user->getEmail(),
                    ));
                    continue;
                }

                if ($this->queue->enqueue($user, $now, $force)) {
                    $queued++;
                }
            }

            $io->success(sprintf('%d report(s) queued.', $queued));
            return Command::SUCCESS;
        } finally {
            $this->release();
        }
    }
}
