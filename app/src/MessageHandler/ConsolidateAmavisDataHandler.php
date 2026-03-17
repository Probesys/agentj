<?php

namespace App\MessageHandler;

use App\Message\ConsolidateAmavisData;
use App\Repository\MsgrcptRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Lock\LockFactory;

#[AsMessageHandler]
final class ConsolidateAmavisDataHandler
{
    public function __construct(
        private MsgrcptRepository $messageRecipientRepository,
        private LoggerInterface $logger,
        private LockFactory $lockFactory,
    ) {
    }

    public function __invoke(ConsolidateAmavisData $consolidateAmavisData): void
    {
        $lock = $this->lockFactory->createLock('consolidate-amavis-data', ttl: 15 * 60);

        if (!$lock->acquire()) {
            $this->logger->error("Can't acquire the consolidate-amavis-data lock, an update is already running.");
            return;
        }

        $updatedCount = $this->messageRecipientRepository->consolidateStatus();

        $this->logger->info("Consolidated {$updatedCount} message recipient(s).");

        $lock->release();
    }
}
