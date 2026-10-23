<?php

namespace App\EventSubscriber;

use App\Message\SendReport;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

class ReportFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(private Connection $connection, private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [WorkerMessageFailedEvent::class => 'onFailure'];
    }

    public function onFailure(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof SendReport || $event->willRetry()) {
            return;
        }

        $this->logger->error('Report retries exhausted; the next scheduled report will cover the unsent period.', [
            'report' => $message->reportId,
            'exception' => $event->getThrowable(),
        ]);
        $this->connection->delete('report_delivery', ['id' => $message->reportId]);
    }
}
