<?php

namespace App\MessageHandler;

use App\Command\TruncateMessageCommand;
use App\Message\CleanData;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CleanDataHandler
{
    private Application $application;

    public function __construct(
        KernelInterface $kernel,
        private LoggerInterface $logger,
        #[Autowire(env: 'int:HISTORY_RETENTION_DAYS')]
        private int $historyRetentionDays,
    ) {
        $this->application = new Application($kernel);
    }

    public function __invoke(CleanData $cleanDataMessage): void
    {
        try {
            $output = new BufferedOutput();
            $input = new ArrayInput([
                'days' => $this->historyRetentionDays,
            ]);
            $command = $this->application->find(TruncateMessageCommand::getDefaultName());

            $command->run($input, $output);

            $this->logger->info("Data cleaned:\n{$output->fetch()}");
        } catch (\Exception $e) {
            $this->logger->error('Failed to clean data.', [
                'exception' => $e,
            ]);
        }
    }
}
