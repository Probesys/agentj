<?php

namespace App\EventListener;

use App\Entity\Domain;
use App\Entity\User;
use App\Service\ReportSchedule;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class ReportScheduleListener
{
    public function __construct(private ReportSchedule $schedule)
    {
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        $entity = $event->getObject();
        if ($entity instanceof User) {
            $entity->setReportNextAt($this->schedule->nextAt($entity, $this->schedule->now(), inclusive: true));
        }
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $entity = $event->getObject();
        $changes = $event->getEntityChangeSet();
        if ($entity instanceof User) {
            $fields = ['report', 'reportTimes', 'reportSkipWeekends', 'domain', 'email', 'roles', 'originalUser'];

            if (array_intersect($fields, array_keys($changes))) {
                $entity->setReportNextAt($this->schedule->nextAt($entity, $this->schedule->now(), inclusive: true));
            }
        } elseif (
            $entity instanceof Domain
            && array_intersect(['active', 'reportTimes', 'reportSkipWeekends'], array_keys($changes))
        ) {
            $this->updateDomainSchedules($entity, $event);
        }
    }

    private function updateDomainSchedules(Domain $domain, PreUpdateEventArgs $event): void
    {
        $connection = $event->getObjectManager()->getConnection();
        if (!$domain->getActive()) {
            $connection->update('users', ['report_next_at' => null], ['domain_id' => $domain->getId()]);
            return;
        }

        $sql = <<<SQL
            SELECT id, report_times, report_skip_weekends
            FROM users
            WHERE domain_id = :domain AND report = 1 AND roles = :roles AND original_user_id IS NULL
        SQL;
        if (!$event->hasChangedField('active')) {
            $inherited = [];
            if ($event->hasChangedField('reportTimes')) {
                $inherited[] = 'report_times IS NULL';
            }
            if ($event->hasChangedField('reportSkipWeekends')) {
                $inherited[] = 'report_skip_weekends IS NULL';
            }
            $sql .= ' AND (' . implode(' OR ', $inherited) . ')';
        }

        $rows = $connection->executeQuery($sql, [
            'domain' => $domain->getId(),
            'roles' => '["ROLE_USER"]',
        ])->fetchAllAssociative();
        $updates = [];
        $now = $this->schedule->now();
        foreach ($rows as $row) {
            /** @var list<string>|null $times */
            $times = $row['report_times'] === null
                ? null
                : json_decode((string) $row['report_times'], true, flags: JSON_THROW_ON_ERROR);
            $nextAt = $this->schedule->nextOccurrence(
                $times ?? $domain->getReportTimes(),
                $row['report_skip_weekends'] === null
                    ? $domain->getReportSkipWeekends()
                    : (bool) $row['report_skip_weekends'],
                $now,
                inclusive: true,
            );
            $updates[$nextAt ?? 0][] = (int) $row['id'];
        }

        foreach ($updates as $nextAt => $ids) {
            $connection->executeStatement('UPDATE users SET report_next_at = :nextAt WHERE id IN (:ids)', [
                'nextAt' => $nextAt === 0 ? null : $nextAt,
                'ids' => $ids,
            ], ['nextAt' => ParameterType::INTEGER, 'ids' => ArrayParameterType::INTEGER]);
        }
    }
}
