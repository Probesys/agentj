<?php

namespace App\Repository;

use App\Amavis\ContentType;
use App\Amavis\DeliveryStatus;
use App\Amavis\MessageStatus;
use App\Entity\Msgs;
use App\Entity\User;
use Doctrine\DBAL;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseMessageRepository<Msgs>
 */
class MsgsRepository extends BaseMessageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Msgs::class);
    }

    public function findOneByMailId(int $partitionTag, string $mailId): ?Msgs
    {
        return $this->findOneBy([
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
        ]);
    }

    /**
     * @param ?array{
     *     sort: string,
     *     direction: string,
     * } $sortParams
     * @return array<int, array<string, mixed>>
     */
    public function advancedSearch(
        ?User $user = null,
        string $messageType = 'incoming',
        ?array $sortParams = null,
    ): array {
        $conn = $this->getEntityManager()->getConnection();

        if ($messageType === 'incoming') {
            $table = 'msgs';
            $msgrcptTable = 'msgrcpt';
            $userJoinCondition = 'u.email = maddr.email';
        } elseif ($messageType === 'outgoing') {
            $table = 'out_msgs';
            $msgrcptTable = 'out_msgrcpt';
            $userJoinCondition = 'u.email = m.from_addr';
        } else {
            throw new \DomainException("messageType must be one of 'incoming' or 'outgoing' (got {$messageType})");
        }

        $parameters = [];
        $types = [];

        $sql = <<<SQL
            SELECT
                m.*,
                mr.status_id,
                m.partition_tag,
                maddr.email,
                m.subject,
                m.from_addr,
                m.time_num,
                mr.rid,
                mr.bspam_level,
                mr.amavis_output,
                CASE
                    WHEN m.subject LIKE 'Re:%'
                    OR m.subject LIKE 'RE:%'
                    THEN 'oui'
                    ELSE 'non'
                END as replyTo
            FROM {$table} m
            LEFT JOIN {$msgrcptTable} mr ON m.mail_id = mr.mail_id
            LEFT JOIN maddr ON maddr.id = mr.rid
            LEFT JOIN users u ON {$userJoinCondition}
            LEFT JOIN domain d ON u.domain_id = d.id
            WHERE d.active = 1
        SQL;

        // if $user is an admin, add a condition to check only the domains he administer
        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            $domains = $user->getDomains()->toArray();
            if ($user->getDomain()) {
                $domains[] = $user->getDomain();
            }

            if (empty($domains)) {
                return [];
            }

            $sql .= ' and u.domain_id in (:domains) ';
            $parameters['domains'] = array_map(function ($domain) {
                return $domain->getId();
            }, $domains);
            $types['domains'] = DBAL\ArrayParameterType::INTEGER;
        }

        if ($sortParams) {
            $sortField = $sortParams['sort'];
            $sortDirection = $sortParams['direction'];

            if (!in_array($sortField, ['mail_id', 'from_addr', 'email', 'subject', 'time_iso'])) {
                $sortField = 'm.time_num';
            }

            if ($sortDirection !== 'asc' && $sortDirection !== 'desc') {
                $sortDirection = 'desc';
            }

            $sql .= " ORDER BY {$sortField} {$sortDirection}";
        } else {
            $sql .= ' ORDER BY m.time_num desc, m.status_id';
        }

        return $conn->executeQuery($sql, $parameters, $types)->fetchAllAssociative();
    }

    /**
     * get all message which need human authentication
     *
     * @return Msgs[]
     */
    public function searchMsgsToSendAuthRequest(): array
    {
        $query = $this->getEntityManager()->createQuery(<<<SQL
            SELECT m
            FROM App\Entity\Msgs m
            WHERE
                (m.isMlist IS NULL OR m.isMlist = 0)
                AND m.status = :statusUntreated
                AND m.sendCaptcha = 0
                AND m.content NOT IN (:content)
                AND DATEDIFF(CURRENT_TIMESTAMP(), m.timeIso) <= 7
            SQL);

        $query->setParameter('statusUntreated', MessageStatus::UNTREATED);
        $query->setParameter('content', [
            ContentType::CLEAN,
            ContentType::VIRUS,
            ContentType::UNCHECKED,
        ]);

        return $query->getResult();
    }

    /**
     * Check the last request sent
     */
    public function getDaysSinceLastRequest(string $to, string $from): ?int
    {
        $query = $this->getEntityManager()->createQuery(<<<SQL
            SELECT DATEDIFF(CURRENT_TIMESTAMP(), m.timeIso) as time_diff
            FROM App\Entity\Msgs m
            LEFT JOIN App\Entity\Msgrcpt mr WITH m.mailId = mr.mailId AND m.partitionTag = mr.partitionTag
            LEFT JOIN App\Entity\Maddr maddr WITH maddr.id = mr.rid
            LEFT JOIN App\Entity\Maddr maddr_sender WITH maddr_sender.id = m.sid
            WHERE
                maddr.email = :to
                AND maddr_sender.email = :from
                AND m.sendCaptcha != 0
            ORDER BY m.timeIso DESC
        SQL);

        $query->setParameter('to', $to);
        $query->setParameter('from', $from);
        $query->setMaxResults(1);

        try {
            $result = $query->getSingleScalarResult();
            return (int) $result;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Get all messages from emailSender to emailRecipient and not delivered yet.
     * @return array<int, array<string, mixed>>
     */
    public function getMessagesToRelease(string $emailSender, string $emailRecipient): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
            SELECT m.*, mr.email as recept_mail, ms.email as sender_email,msr.rid FROM msgs m
            LEFT JOIN msgrcpt msr ON m.mail_id = msr.mail_id
            LEFT JOIN maddr ms ON ms.id = m.sid
            LEFT JOIN maddr mr ON mr.id = msr.rid
            WHERE msr.ds != :deliveryPass
            AND msr.status_id != :statusVirus
            AND msr.status_id != :statusAuthorized
            AND msr.status_id != :statusRestored
            AND mr.email = :emailRecipient
            AND ms.email = :emailSender
        SQL;

        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery([
            'deliveryPass' => DeliveryStatus::PASS,
            'statusVirus' => MessageStatus::VIRUS,
            'statusAuthorized' => MessageStatus::AUTHORIZED,
            'statusRestored' => MessageStatus::RESTORED,
            'emailRecipient' => $emailRecipient,
            'emailSender' => $emailSender,
        ])->fetchAllAssociative();
    }

    /**
     * Delete message older $date
     *
     * @return array{
     *     nbDeletedMsgs: int,
     *     nbDeletedQuarantine: int,
     * }
     */
    public function truncateMessageOlder(int $date): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = ' DELETE q FROM quarantine q '
            . ' LEFT JOIN  msgs m ON m.mail_id = q.mail_id '
            . ' WHERE m.time_num < :date';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('date', $date, DBAL\ParameterType::INTEGER);
        $result = $stmt->executeQuery();
        $nbDeletedQuarantine = $result->rowCount();

        $sql = ' DELETE FROM msgs WHERE time_num < :date';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('date', $date, DBAL\ParameterType::INTEGER);
        $result = $stmt->executeQuery();
        $nbDeletedMsgs = $result->rowCount();

        return [
            'nbDeletedMsgs' => $nbDeletedMsgs,
            'nbDeletedQuarantine' => $nbDeletedQuarantine,
        ];
    }
}
