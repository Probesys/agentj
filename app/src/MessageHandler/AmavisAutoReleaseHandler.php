<?php

namespace App\MessageHandler;

use App\Command\AmavisAutoReleaseCommand;
use App\Message\AmavisAutoRelease;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AmavisAutoReleaseHandler
{
    private Application $application;

    public function __construct(
        KernelInterface $kernel,
        private LoggerInterface $logger,
    ) {
        $this->application = new Application($kernel);
    }

    public function __invoke(AmavisAutoRelease $amavisAutoReleaseMessage): void
    {
        try {
            $output = new BufferedOutput();
            $input = new ArrayInput([]);
            $command = $this->application->find('agentj:auto-release-message');

            $command->run($input, $output);
        } catch (\Exception $e) {
            $this->logger->error('Failed to automatically release messages.', [
                'exception' => $e,
            ]);
        }
    }
}
