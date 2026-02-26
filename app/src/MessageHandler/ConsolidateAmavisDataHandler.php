<?php

namespace App\MessageHandler;

use App\Message\ConsolidateAmavisData;
use App\Repository\MsgrcptRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

#[AsMessageHandler]
final class ConsolidateAmavisDataHandler
{
    public function __construct(
        private MsgrcptRepository $messageRecipientRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ConsolidateAmavisData $consolidateAmavisData): void
    {
        $store = new SemaphoreStore();
        $factory = new LockFactory($store);
        $lock = $factory->createLock('consolidate-amavis-data', 15 * 60);

        if (!$lock->acquire()) {
            $this->logger->error("Can't acquire the consolidate-amavis-data lock, an update is already running.");
            return;
        }

        $updatedCount = $this->messageRecipientRepository->consolidateStatus();

        $this->logger->info("Consolidated {$updatedCount} message recipient(s).");

        $lock->release();
    }
}
