<?php

namespace App\MessageHandler;

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
        private EntityManagerInterface $entityManager,
        private LockFactory $lockFactory,
    ) {
        $this->application = new Application($kernel);
    }

    public function __invoke(SynchronizeConnectors $message): void
    {
        $type = $message->getType();

        $lock = $this->lockFactory->createLock('synchronize-connectors', ttl: 3600);

        if (!$lock->acquire()) {
            $this->logger->info(
                'Cannot acquire the synchronize-connectors lock, the handler is probably already running.'
            );
            return;
        }

        $connectors = $this->connectorRepository->getActiveConnectors();

        foreach ($connectors as $connector) {
            try {
                $output = new BufferedOutput();
                $input = new ArrayInput([
                    'connectorId' => $connector->getId(),
                ]);
                if ($connector instanceof LdapConnector && ($type === 'ldap' || $type === 'all')) {
                    $command = $this->application->find('agentj:import-ldap');
                } elseif ($connector instanceof Office365Connector && ($type === 'o365' || $type === 'all')) {
                    $command = $this->application->find('agentj:import-office365');
                } else {
                    continue;
                }

                $connector->setImportStartedAt(new \DateTimeImmutable());
                $this->entityManager->persist($connector);
                $this->entityManager->flush();

                $command->run($input, $output);

                $connector->setImportStartedAt(null);
                $connector->setLastSynchronizedAt(new \DateTimeImmutable());
                $connector->setLastResultSynchronization($output->fetch());
                $this->entityManager->persist($connector);
                $this->entityManager->flush();

                $this->logger->error('Connector synchronized', [
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

        $lock->release();
    }
}
