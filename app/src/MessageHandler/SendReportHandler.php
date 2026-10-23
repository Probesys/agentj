<?php

namespace App\MessageHandler;

use App\Entity\ReportDelivery;
use App\Message\SendReport;
use App\Repository\ReportDeliveryRepository;
use App\Service\ReportMailBuilder;
use App\Service\ReportSchedule;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
class SendReportHandler
{
    public function __construct(
        private ReportDeliveryRepository $deliveries,
        private ReportMailBuilder $builder,
        private ReportSchedule $schedule,
        private TransportInterface $transport,
        private LockFactory $lockFactory,
        private LoggerInterface $logger,
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(SendReport $message): void
    {
        $lock = $this->lockFactory->createLock('report-delivery-' . $message->reportId, ttl: 600);
        if (!$lock->acquire()) {
            throw new RecoverableMessageHandlingException('This report is already being processed.', retryDelay: 1000);
        }

        try {
            $report = $this->deliveries->findForSending($message->reportId);
            if ($report === null) {
                return;
            }

            $this->send($report);
        } finally {
            $lock->release();
        }
    }

    private function send(ReportDelivery $report): void
    {
        $user = $report->getUser();
        if (!$this->schedule->isEnabled($user) || $user->getEmail() !== $report->getEmail()) {
            $this->logger->info('Report cancelled because its recipient is no longer eligible.', [
                'report' => $report->getId(),
            ]);
            $this->deliveries->remove($report);
            return;
        }

        $now = $this->schedule->now();
        if (!$report->isForced()) {
            $deferUntil = $this->schedule->deferUntil($user, $report->getScheduledAt(), $now);
            if ($deferUntil !== null) {
                $id = $report->getId();
                if ($id === null) {
                    throw new \LogicException('The pending report has no identifier.');
                }
                $this->bus->dispatch(new SendReport($id), [
                    new DelayStamp(($deferUntil - $now->getTimestamp()) * 1000),
                ]);
                $this->logger->info('Report deferred to the next allowed sending time.', [
                    'report' => $id,
                    'nextAt' => $deferUntil,
                ]);
                return;
            }
        }

        $content = $report->getContent();
        if ($content === null) {
            $content = $this->builder->prepare($user, $now->getTimestamp());
            if ($content === null) {
                $this->logger->info('Empty report skipped.', ['report' => $report->getId()]);
                $this->deliveries->remove($report);
                return;
            }

            $report->prepare($content, $now->getTimestamp());
            $this->deliveries->save($report);
        }

        $email = $this->builder->createEmail($content, $report->getEmail());
        $sent = $this->transport->send($email);
        if ($sent === null) {
            $this->logger->warning('Report rejected by the mail transport.', ['report' => $report->getId()]);
            $this->deliveries->remove($report);
            return;
        }

        $this->deliveries->complete($report, $this->schedule->now()->getTimestamp());
        $this->logger->info('Report accepted by the SMTP transport.', ['email' => $report->getEmail()]);
    }
}
