<?php

namespace App\Repository;

use App\Amavis\ContentType;
use App\Amavis\DeliveryStatus;
use App\Amavis\MessageStatus;
use App\Entity\Domain;
use App\Entity\MessageRecipient;
use App\Entity\Message;
use App\Entity\User;
use App\Util\Url;
use Doctrine\DBAL;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<MessageRecipient>
 */
class MessageRecipientRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageRecipient::class);
    }

    public function findOneByMessageAndRecipientAddressId(Message $message, int $addressId): ?MessageRecipient
    {
        return $this->findOneBy([
            'partitionTag' => $message->getPartitionTag(),
            'mailId' => $message->getMailId(),
            'address' => $addressId,
        ]);
    }

    /**
     * @return MessageRecipient[]
     */
    public function findByMessage(Message $message): array
    {
        return $this->findBy([
            'partitionTag' => $message->getPartitionTag(),
            'mailId' => $message->getMailId(),
        ]);
    }

    /**
     * @param null|array{timeNum: int, partitionTag: int, mailId: string, rseqnum: int} $cursor
     * @return MessageRecipient[]
     */
    public function findSpammedSinceAfter(int $since, int $until, ?array $cursor, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('mr')
            ->innerJoin('mr.message', 'm')
            ->where('mr.status = :status')
            ->andWhere('m.timeNum >= :since')
            ->andWhere('m.timeNum <= :until')
            ->andWhere('mr.amavisReleaseStartedAt IS NULL')
            ->andWhere(<<<'DQL'
                NOT EXISTS (
                    SELECT 1
                    FROM App\Entity\Log manualSpamLog
                    WHERE manualSpamLog.mailId = mr.mailId
                    AND manualSpamLog.action IN (:manualSpamActions)
                )
            DQL)
            ->setParameter('status', MessageStatus::SPAMMED)
            ->setParameter('since', $since)
            ->setParameter('until', $until)
            ->setParameter('manualSpamActions', ['marked as spam', 'marked as spam batch'])
            ->setMaxResults($limit)
            ->orderBy('m.timeNum', 'ASC')
            ->addOrderBy('mr.partitionTag', 'ASC')
            ->addOrderBy('mr.mailId', 'ASC')
            ->addOrderBy('mr.rseqnum', 'ASC');

        if ($cursor !== null) {
            $queryBuilder->andWhere(<<<'DQL'
                (
                m.timeNum > :cursorTimeNum
                OR (m.timeNum = :cursorTimeNum AND mr.partitionTag > :cursorPartitionTag)
                OR (
                    m.timeNum = :cursorTimeNum
                    AND mr.partitionTag = :cursorPartitionTag
                    AND mr.mailId > :cursorMailId
                )
                OR (
                    m.timeNum = :cursorTimeNum
                    AND mr.partitionTag = :cursorPartitionTag
                    AND mr.mailId = :cursorMailId
                    AND mr.rseqnum > :cursorRseqnum
                )
                )
            DQL)
                ->setParameter('cursorTimeNum', $cursor['timeNum'])
                ->setParameter('cursorPartitionTag', $cursor['partitionTag'])
                ->setParameter('cursorMailId', $cursor['mailId'])
                ->setParameter('cursorRseqnum', $cursor['rseqnum']);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Return all message recipients sent by $senderFrom to $recipientUser.
     *
     * This method is used to fetch the messages sent by a sender who just had
     * been authorized or banned by a user. It fetches the messages by using
     * the raw "From" value. We cannot pass the "senderEmail" value because it
     * is either the email part of the From value, or the envelope sender
     * address if From is empty. In other words, this value cannot be used to
     * fetch the mails, and the envelope sender address is not reliable as it
     * can be some random emails (e.g. mails sent by newsletter operators).
     *
     * It is highly recommended to recheck the senderEmail on the messages
     * returned by this method in order to be sure to release or ban the
     * correct messages.
     *
     * @return MessageRecipient[]
     */
    public function findSentToUserByEmail(User $recipientUser, string $senderFrom): array
    {
        $query = $this->getEntityManager()->createQuery(<<<SQL
            SELECT mr
            FROM App\Entity\MessageRecipient as mr
            JOIN mr.message m
            JOIN mr.address ra
            WHERE ra.email = :recipientEmail
            AND m.fromAddr = :senderFrom
            AND mr.status IS NOT NULL
        SQL);

        $query->setParameter('recipientEmail', $recipientUser->getEmail());
        $query->setParameter('senderFrom', $senderFrom);

        return $query->getResult();
    }

    /**
     * Return all message recipients sent by $senderFrom to $recipientDomain.
     *
     * As for findSentToUserByEmail, this method fetches the messages by using
     * the raw "From" value. See above for the reasons.
     *
     * It is highly recommended to recheck the senderEmail on the messages
     * returned by this method in order to be sure to release or ban the
     * correct messages.
     *
     * @return MessageRecipient[]
     */
    public function findSentToDomainByEmail(Domain $recipientDomain, string $senderFrom): array
    {
        $query = $this->getEntityManager()->createQuery(<<<SQL
            SELECT mr
            FROM App\Entity\MessageRecipient as mr
            JOIN mr.message m
            JOIN mr.address ra
            WHERE ra.domain = :recipientReverseDomainName
            AND m.fromAddr = :senderFrom
            AND mr.status IS NOT NULL
        SQL);

        $domainName = $recipientDomain->getDomain();
        $reverseDomainName = Url::reverseDomainName($domainName);

        $query->setParameter('recipientReverseDomainName', $reverseDomainName);
        $query->setParameter('senderFrom', $senderFrom);

        return $query->getResult();
    }

    /**
     * Update the status of a message for one recipient
     */
    public function changeStatus(int $partitionTag, string $mailId, int $status, int|string $addressId): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
            UPDATE msgrcpt SET status_id = :status
            WHERE partition_tag = :partitionTag
            AND mail_id = :mailId
            AND rid = :addressId

        SQL;

        $conn->executeStatement($sql, [
            'status' => $status,
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
            'addressId' => $addressId,
        ], [
            'status' => DBAL\ParameterType::INTEGER,
            'partitionTag' => DBAL\ParameterType::STRING,
            'mailId' => DBAL\ParameterType::STRING,
            'addressId' => DBAL\ParameterType::STRING,
        ]);
    }

    /**
     * @return MessageRecipient[]
     */
    public function findByEmailRecipient(User $user): array
    {
        $query = $this->getEntityManager()->createQuery(<<<SQL
            SELECT mr
            FROM App\Entity\MessageRecipient as mr
            JOIN mr.address AS ra
            WHERE ra.email = :email
        SQL);

        $query->setParameter('email', $user->getEmail());

        return $query->getResult();
    }

    public function consolidateStatus(): int|string
    {
        $connection = $this->getEntityManager()->getConnection();

        // phpcs:disable Generic.Files.LineLength
        $statement = $connection->prepare(<<<SQL
            UPDATE msgrcpt mr
            INNER JOIN maddr ma ON (mr.rid = ma.id)
            INNER JOIN users u ON (ma.email = u.email)
            INNER JOIN domain d ON (u.domain_id = d.id)
            SET status_id = CASE
                WHEN mr.ds = :ds_pass AND mr.wl = 'Y' THEN :status_authorized
                WHEN mr.ds = :ds_pass AND mr.wl != 'Y' THEN :status_restored
                WHEN mr.ds != :ds_pass AND mr.content = :content_virus THEN :status_virus
                WHEN mr.ds != :ds_pass AND mr.content != :content_virus AND mr.bl = 'Y' THEN :status_banned
                WHEN mr.ds != :ds_pass AND mr.content != :content_virus AND mr.bl != 'Y'  THEN :status_unreleased
                ELSE NULL
            END
            WHERE status_id IS NULL
        SQL);
        // phpcs:enable Generic.Files.LineLength

        $statement->bindValue('ds_pass', DeliveryStatus::PASS);
        $statement->bindValue('content_virus', ContentType::VIRUS);
        $statement->bindValue('status_authorized', MessageStatus::AUTHORIZED);
        $statement->bindValue('status_restored', MessageStatus::RESTORED);
        $statement->bindValue('status_virus', MessageStatus::VIRUS);
        $statement->bindValue('status_banned', MessageStatus::BANNED);
        $statement->bindValue('status_unreleased', MessageStatus::UNRELEASED);

        return $statement->executeQuery()->rowCount();
    }
}
