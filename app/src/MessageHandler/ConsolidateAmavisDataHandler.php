<?php

namespace App\MessageHandler;

use App\Message\ConsolidateAmavisData;
use App\Repository\MessageRecipientRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ConsolidateAmavisDataHandler
{
    public function __construct(
        private MessageRecipientRepository $messageRecipientRepository,
        private LoggerInterface $logger,
        private LockFactory $lockFactory,
    ) {
    }

    public function __invoke(ConsolidateAmavisData $consolidateAmavisData): void
    {
        $lock = $this->lockFactory->createLock('consolidate-amavis-data', ttl: 15 * 60);

        if (!$lock->acquire()) {
            $this->logger->info("Can't acquire the consolidate-amavis-data lock, an update is already running.");
            return;
        }

        $updatedCount = $this->messageRecipientRepository->consolidateStatus();

        $this->logger->info("Consolidated {$updatedCount} message recipient(s).");

        $lock->release();
    }
}
