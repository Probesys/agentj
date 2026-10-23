<?php

namespace App\Service;

use App\Entity\User;
use App\Message\SendReport;
use App\Repository\ReportDeliveryRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ReportQueue
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReportSchedule $schedule,
        private MessageBusInterface $bus,
        private ReportDeliveryRepository $deliveries,
    ) {
    }

    public function enqueue(User $user, DateTimeImmutable $now, bool $force = false): bool
    {
        $connection = $this->em->getConnection();
        return $connection->transactional(function (Connection $connection) use ($user, $now, $force): bool {
            // Serialize schedulers before checking this recipient's pending delivery.
            $connection->executeQuery('SELECT id FROM users WHERE id = :id FOR UPDATE', [
                'id' => $user->getId(),
            ])->fetchOne();
            $this->em->refresh($user);
            if (!$this->schedule->isEnabled($user) || $this->deliveries->hasPending($user)) {
                return false;
            }

            $nextAt = $user->getReportNextAt();
            if (!$force) {
                if ($nextAt === null || $nextAt > $now->getTimestamp()) {
                    return false;
                }

                $deferUntil = $this->schedule->deferUntil($user, $nextAt, $now);
                if ($deferUntil !== null) {
                    $connection->update('users', ['report_next_at' => $deferUntil], ['id' => $user->getId()]);
                    $this->em->refresh($user);
                    return false;
                }
            }

            $connection->insert('report_delivery', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'scheduled_at' => $force ? $now->getTimestamp() : $nextAt,
                'forced' => (int) $force,
            ]);
            $id = (int) $connection->lastInsertId();
            $connection->update('users', [
                'report_next_at' => $this->schedule->nextAt($user, $now),
            ], ['id' => $user->getId()]);
            $this->em->refresh($user);

            $this->bus->dispatch(new SendReport($id));

            return true;
        });
    }
}
