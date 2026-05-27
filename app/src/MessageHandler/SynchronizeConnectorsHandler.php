<?php

namespace App\MessageHandler;

use App\Entity\Connector;
use App\Entity\LdapConnector;
use App\Entity\Office365Connector;
use App\Message\SynchronizeConnectors;
use App\Repository\ConnectorRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SynchronizeConnectorsHandler
{
    private Application $application;

    public function __construct(
        KernelInterface $kernel,
        private ConnectorRepository $connectorRepository,
        private LoggerInterface $logger,
        private LockFactory $lockFactory,
    ) {
        $this->application = new Application($kernel);
    }

    public function __invoke(SynchronizeConnectors $message): void
    {
        $id = $message->getId();

        $lock = $this->lockFactory->createLock('synchronize-connectors', ttl: 3600);

        if (!$lock->acquire()) {
            $this->logger->info(
                'Cannot acquire the synchronize-connectors lock, the handler is probably already running.'
            );
            return;
        }

        $connectors = $this->connectorRepository->getActiveConnectors();
        $connectors = array_filter($connectors, function (Connector $connector) use ($id): bool {
            return $id === 'all' || $connector->getId() === $id;
        });

        foreach ($connectors as $connector) {
            try {
                $output = new BufferedOutput();
                $input = new ArrayInput([
                    'connectorId' => $connector->getId(),
                ]);
                if ($connector instanceof LdapConnector) {
                    $command = $this->application->find('agentj:import-ldap');
                } elseif ($connector instanceof Office365Connector) {
                    $command = $this->application->find('agentj:import-office365');
                } else {
                    continue;
                }

                $command->run($input, $output);

                $this->logger->info('Connector synchronized', [
                    'connector_id' => $connector->getId(),
                    'connector_type' => $connector->getType(),
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to synchronize connector', [
                    'connector_id' => $connector->getId(),
                    'connector_type' => $connector->getType(),
                    'exception' => $e,
                ]);
            }
        }

        $lock->release();
    }
}
