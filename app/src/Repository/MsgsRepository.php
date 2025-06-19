<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Amavis\MessageStatus;
use App\Entity\Msgs;
use App\Entity\User;
use App\Amavis\ContentType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Msgs>
 */
class MsgsRepository extends ServiceEntityRepository
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
     * Construct the SQL fragement of the search request
     *
     * @param ?User[] $alias
     */
    private function getSearchMsgSqlWhere(
        ?User $user = null,
        ?int $type = null,
        ?array $alias = null,
        ?int $fromDate = null
    ): string {
        $email = null;
        $sqlWhere = ' WHERE d.active=1  ';
        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            $domain = $user->getDomain();
            if ($domain !== null) {
                $domainsIds = [$domain->getId()];
            } else {
                $domainsIds = [];
            }
            $domains = $user->getDomains();
            if ($domains !== null && !$domains->isEmpty()) {
                $domainsIds = array_merge($domainsIds, $domains->map(function ($domain) {
                    return $domain->getId();
                })->toArray());
            }

            if (empty($domainsIds)) {
                return ' WHERE 1=0  ';
            }

            $sqlWhere .= ' AND u.domain_id in (' . implode(',', $domainsIds) . ') ';
        }

        if ($type) {
            switch ($type) {
            case MessageStatus::SPAMMED: //spam and
                $sqlWhere .= ' and mr.status_id is null and  bspam_level > d.level and mr.content != "C" and mr.content != "V"  ';
                break;
            case MessageStatus::VIRUS: //spam and
                $sqlWhere .= ' and mr.content = "V" ';
                break;
            case MessageStatus::BANNED:
                $sqlWhere .= ' and (mr.status_id=1 or mr.bl = "Y")  and mr.content != "V"';
                break;
            case MessageStatus::AUTHORIZED:
                //$sqlWhere .= ' and (mr.status_id=2 or (mr.wl = "Y" and mr.status_id != 3)) ';
                $sqlWhere .= ' and (mr.status_id=2 or mr.wl = "Y" or (mr.content="Y" and (mr.status_id = 2 or mr.status_id is null))) and mr.content != "V"';
                break;
            case MessageStatus::DELETED:
                $sqlWhere .= ' and mr.status_id=3 and mr.content != "V"  ';
                break;
            case MessageStatus::RESTORED:
                $sqlWhere .= ' and mr.status_id=5 and mr.content != "V"  ';
                break;
            case null:
                $sqlWhere .= ' ';
                break;
            default:
                $sqlWhere .= ' and bspam_level <= d.level and mr.content != "C" and mr.content != "V" and  mr.status_id=' . $type .  ' ';
                break;
            }
        } else {
            $sqlWhere .= ' and mr.content != "C"  and mr.content != "V" and mr.content != "Y" AND mr.wl != "Y" AND mr.bl != "Y"  and ( mr.status_id IS NULL  OR mr.status_id = 4 ) and bspam_level <= d.level ';
        }

        if ($user && $user->getEmail() && in_array('ROLE_USER', $user->getRoles())) {
            $email = stream_get_contents($user->getEmail(), -1, 0);
            if ($email) {
                $sqlWhere .= ' AND ( maddr.email = "' . $email . '" ';

                if ($alias) {
                    foreach ($alias as $userAlias) {
                        $emailAlias = stream_get_contents($userAlias->getEmail(), -1, 0);
                        $sqlWhere .= ' OR maddr.email = "' . $emailAlias . '" ';
                    }
                }
                $sqlWhere .= ') ';
            }
        }

        if (!is_null($fromDate)) {
            $sqlWhere .= "AND m.time_num > " . $fromDate;
        }

        return $sqlWhere;
    }

    /**
     * Count the number of message by $type (banned, etc..)
     *
     * @param User[] $alias
     */
    public function countByType(?User $user = null, ?int $type = null, array $alias = []): int
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'select count(m.mail_id) as nb_result from msgs m '
            . 'LEFT JOIN msgrcpt mr ON m.mail_id = mr.mail_id '
            . 'LEFT JOIN maddr ON maddr.id = mr.rid '
            . 'LEFT JOIN message_status ms ON m.status_id = ms.id '
            . 'left join users u on u.email=maddr.email '
            . 'left join domain d on u.domain_id=d.id ';

        $sql .= $this->getSearchMsgSqlWhere($user, $type, $alias);

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery()->fetchAssociative();

        if ($result) {
            return $result['nb_result'];
        } else {
            return 0;
        }
    }

    /**
     * Count the number of message by $type (banned, etc..) and day
     *
     * @param ?User[] $alias
     * @return array<int, array<string, mixed>>
     */
    public function countByTypeAndDays(?User $user = null, ?int $type = null, ?array $alias = null, ?\DateTime $day = null, ?Domain $domain = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'select count(m.mail_id) as nb_result,m.time_iso  from msgs m '
            . 'LEFT JOIN msgrcpt mr ON m.mail_id = mr.mail_id '
            . 'LEFT JOIN maddr ON maddr.id = mr.rid '
            . 'LEFT JOIN message_status ms ON m.status_id = ms.id '
            . 'left join users u on u.email=maddr.email '
            . 'left join domain d on u.domain_id=d.id ';

        if ($day){
            $sql.= " AND date(m.time_iso) = '" . $day->format('Y-m-d') . "'";
        }

        if ($domain){
            $sql.= " AND d.id = '" . $domain->getId() . "'";
        }

        // if $user is an admin, add a condition to check only the domains he administer
        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            $domainsIds = [];
            if ($user->getDomain()) {
                $domainsIds[] = $user->getDomain()->getId();
            }
            $domains = $user->getDomains();
            if ($domains !== null && !$domains->isEmpty()) {
                $domainsIds = array_merge($domainsIds, $domains->map(function ($domain) {
                    return $domain->getId();
                })->toArray());
            }

            if (empty($domainsIds)) {
                return [];
            }

            $sql .= ' AND u.domain_id in (' . implode(',', $domainsIds) . ') ';
        }

        $sql .= $this->getSearchMsgSqlWhere($user, $type, $alias);

        $sql .= " GROUP BY SUBSTRING(m.time_iso, 1, 8) ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        return $result;
    }

    /**
     * search query
     *
     * @param ?User[] $alias
     * @param ?array{
     *     sort: string,
     *     direction: string,
     * } $sortParams
     * @return array<int, array<string, mixed>>
     */
    public function search(?User $user = null, ?int $type = null, ?array $alias = null, ?string $searchKey = null, ?array $sortParams = null, ?int $fromDate = null, ?int $limit = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT m.mail_id, m.message_error, mr.status_id, ms.name, m.partition_tag, maddr.email, m.subject, m.from_addr, m.time_num, mr.rid, mr.bspam_level, '
            . 'CASE '
            . 'WHEN ms.name IS NOT NULL THEN ms.name '
            . 'WHEN mr.status_id IS NULL AND mr.bspam_level > d.level AND mr.content != "C" AND mr.content != "V" THEN "spam" '
            . 'WHEN mr.content = "V" THEN "virus" '
            . 'ELSE "untreated" '
            . 'END AS status_description '
            . 'FROM msgs m '
            . 'LEFT JOIN msgrcpt mr ON m.mail_id = mr.mail_id '
            . 'LEFT JOIN maddr ON maddr.id = mr.rid '
            . 'LEFT JOIN message_status ms ON mr.status_id = ms.id '
            . 'LEFT JOIN users u ON u.email = maddr.email '
            . 'LEFT JOIN domain d ON u.domain_id = d.id';

        $sql .= $this->getSearchMsgSqlWhere($user, $type, $alias, $fromDate);

        if ($searchKey) {
            // Check if $user is an admin
            $isAdmin = $user && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()));
            if ($isAdmin) {
                $sql .= ' AND (m.subject LIKE :searchKey OR maddr.email LIKE :searchKey OR m.from_addr LIKE :searchKey) ';
            } else {
                $sql .= ' AND (m.subject LIKE :searchKey OR m.from_addr LIKE :searchKey) ';
            }
        }

        if ($sortParams) {
            $sql .= ' ORDER BY ' . $sortParams['sort'] . ' ' . $sortParams['direction'];
        } else {
            $sql .= ' ORDER BY m.time_num desc, m.status_id ';
        }

        if ($limit !== null && is_numeric($limit) && $limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $conn->prepare($sql);

        if ($searchKey) {
            $stmt->bindValue('searchKey', '%' . $searchKey . '%');
        }

        $return = $stmt->executeQuery()->fetchAllAssociative();
        unset($stmt);
        unset($conn);
        return $return;
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
            $domainsIds = [];
            if ($user->getDomain()) {
                $domainsIds[] = $user->getDomain()->getId();
            }
            $domains = $user->getDomains();
            if ($domains !== null && !$domains->isEmpty()) {
                $domainsIds = array_merge($domainsIds, $domains->map(function ($domain) {
                    return $domain->getId();
                })->toArray());
            }

            if (empty($domainsIds)) {
                return [];
            }

            $sql .= ' and u.domain_id in (' . implode(',', $domainsIds) . ') ';
        }

        if ($sortParams) {
            $sql .= ' ORDER BY ' . $sortParams['sort'] . ' ' . $sortParams['direction'];
        } else {
            $sql .= ' ORDER BY m.time_num desc, m.status_id ';
        }

        $stmt = $conn->prepare($sql);

        $allMessages = $stmt->executeQuery()->fetchAllAssociative();

        unset($stmt);
        unset($conn);

        return $allMessages;
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
                AND m.status IS NULL
                AND m.sendCaptcha = 0
                AND m.content NOT IN (:content)
            SQL);

        $query->setParameter('content', [
            ContentType::Clean,
            ContentType::Virus,
            ContentType::Unchecked,
        ]);

        return $query->getResult();
    }

    /**
     * Update status of all message for one sender
     */
    public function updateMessageSender(string $emailSender, string $emailRecipient, int $status): void
    {
        $conn = $this->getEntityManager()->getConnection();
        //mettre le status par rapport au mailid
        $sql = 'UPDATE msgs m'
            . ' LEFT JOIN msgrcpt msr ON m.mail_id = msr.mail_id '
            . ' LEFT JOIN maddr ms ON ms.id = m.sid '
            . ' LEFT JOIN maddr mr ON mr.id = msr.rid '
            . ' SET status_id = "' . $status . '"'
            . ' WHERE ms.email =  "' . $emailSender . '" AND mr.email =  "' . $emailRecipient . '" ';
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery();
    }

    /**
     * Update the status of a message
     */
    public function changeStatus(int $partitiontag, string $mailId, int $status): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'UPDATE msgs SET status_id =  ' . $status . '  WHERE partition_tag = "' . $partitiontag . '" AND mail_id = "' . $mailId . '"';
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery();
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
                AND maddr_sender.email = :from_addr
                AND m.sendCaptcha != 0
            ORDER BY m.timeIso DESC
        SQL);

        $query->setParameter('to', $to);
        $query->setParameter('from_addr', $from);
        $query->setMaxResults(1);

        try {
            $result = $query->getSingleScalarResult();
            return (int) $result;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }

    }

    /**
     * Update the maddr adresses that have a message status error
     */
    public function updateErrorStatus(): void
    {

        $query = $this->getEntityManager()->createQuery(<<<SQL
            UPDATE App\Entity\Maddr m
             SET m.isInvalid = true
             WHERE m.id IN (
                 SELECT IDENTITY(msgs.sid)
                 FROM App\Entity\Msgs msgs
                 WHERE msgs.status = :errorStatus
             )
        SQL);

        $query->setParameter('errorStatus', MessageStatus::ERROR);

        $query->execute();

    }

    /**
     * Get all message from emailSender and rid of receipt with status is null and not clean (content != C)
     * @todo chercher le content = spammy et le rajouter dans le where !=
     * @return array<int, array<string, mixed>>
     */
    public function getAllMessageRecipient(string $emailSender, string $emailRecipient): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT m.*, mr.email as recept_mail, ms.email as sender_email,msr.rid FROM msgs m'
            . ' LEFT JOIN msgrcpt msr ON m.mail_id = msr.mail_id '
            . ' LEFT JOIN maddr ms ON ms.id = m.sid '//sid is sender
            . ' LEFT JOIN maddr mr ON mr.id = msr.rid '//rid is recipient
            . ' WHERE m.content != "C" AND msr.content != "C"  AND mr.email = "' . $emailRecipient . '" AND ms.email =  "' . $emailSender . '"';
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * Delete message older $date
     *
     * @return array{
     *     nbDeletedMsgs: int,
     *     nbDeletedMsgrcpt: int,
     *     nbDeletedQuarantine: int,
     * }
     */
    public function truncateMessageOlder(int $date): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = ' DELETE mr FROM msgrcpt mr '
            . ' LEFT JOIN  msgs m ON m.mail_id = mr.mail_id '
            . ' WHERE m.time_num < ' . $date;

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        $nbDeletedMsgrcpt = $result->rowCount();

        $sql = ' DELETE q FROM quarantine q '
            . ' LEFT JOIN  msgs m ON m.mail_id = q.mail_id '
            . ' WHERE m.time_num < ' . $date;

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        $nbDeletedQuarantine = $result->rowCount();

        $sql2 = ' DELETE FROM msgs WHERE time_num < ' . $date;
        $stmt2 = $conn->prepare($sql2);
        $result = $stmt2->executeQuery();
        $nbDeletedMsgs = $result->rowCount();
        return ['nbDeletedMsgs' => $nbDeletedMsgs, 'nbDeletedMsgrcpt' => $nbDeletedMsgrcpt, 'nbDeletedQuarantine' => $nbDeletedQuarantine];
    }

}
