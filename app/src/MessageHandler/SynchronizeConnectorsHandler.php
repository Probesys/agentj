<?php

namespace App\MessageHandler;

use App\Command\LDAPImportCommand;
use App\Command\Office365ImportCommand;
use App\Entity\LdapConnector;
use App\Entity\Office365Connector;
use App\Message\SynchronizeConnectors;
use App\Repository\ConnectorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SynchronizeConnectorsHandler
{
    private Application $application;

    public function __construct(
        KernelInterface $kernel,
        private ConnectorRepository $connectorRepository,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager
    ) {
        $this->application = new Application($kernel);
    }

    public function __invoke(SynchronizeConnectors $message): void
    {
        $connectors = $this->connectorRepository->getActiveConnectors();

        foreach ($connectors as $connector) {
            try {
                $output = new BufferedOutput();
                $input = new ArrayInput([
                    'connectorId' => $connector->getId(),
                ]);
                if ($connector instanceof LdapConnector) {
                    $command = $this->application->find(LDAPImportCommand::getDefaultName());
                } elseif ($connector instanceof Office365Connector) {
                    $command = $this->application->find(Office365ImportCommand::getDefaultName());
                } else {
                    continue;
                }

                $command->run($input, $output);

                $connector->setLastSynchronizedAt(new \DateTimeImmutable());
                $connector->setLastResultSynchronization($output->fetch());

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
        $this->entityManager->flush();
    }
}
