<?php

namespace App\Repository;

use App\Amavis\DeliveryStatus;
use App\Amavis\ContentType;
use App\Amavis\MessageStatus;
use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use Doctrine\DBAL;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

/**
 * @extends BaseRepository<Msgrcpt>
 */
class MsgrcptRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Msgrcpt::class);
    }

    public function findOneByMessageAndRid(Msgs $message, int $rid): ?Msgrcpt
    {
        return $this->findOneBy([
            'partitionTag' => $message->getPartitionTag(),
            'mailId' => $message->getMailId(),
            'rid' => $rid,
        ]);
    }

    /**
     * @return Msgrcpt[]
     */
    public function findByMessage(Msgs $message): array
    {
        return $this->findBy([
            'partitionTag' => $message->getPartitionTag(),
            'mailId' => $message->getMailId(),
        ]);
    }

    /**
     * Update the status of a message for one recipient
     */
    public function changeStatus(int $partitiontag, string $mailId, int $status, int $rid): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
            UPDATE msgrcpt SET status_id = :status
            WHERE partition_tag = :partitionTag
            AND mail_id = :mailId
            AND rid = :rid
        SQL;

        $conn->executeStatement($sql, [
            'status' => $status,
            'partitionTag' => $partitiontag,
            'mailId' => $mailId,
            'rid' => $rid,
        ], [
            'status' => DBAL\ParameterType::INTEGER,
            'partitionTag' => DBAL\ParameterType::STRING,
            'mailId' => DBAL\ParameterType::STRING,
            'rid' => DBAL\ParameterType::INTEGER,
        ]);
    }

    /**
     * Get all message from emailSender and rid of receipt with status is null and not clean (content != C)
     * @todo chercher le content = spammy et le rajouter dans le where !=
     * @return array<array<string, mixed>>
     */
    public function getMessagesToReleaseForDomain(string $emailSender, string $domain): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = <<<SQL
            SELECT m.*, mr.email as recept_mail, ms.email as sender_email,msr.rseqnum FROM msgs m
            LEFT JOIN msgrcpt msr ON m.mail_id = msr.mail_id
            LEFT JOIN maddr ms ON ms.id = m.sid
            LEFT JOIN maddr mr ON mr.id = msr.rid
            WHERE  msr.ds != :deliveryPass
            AND msr.status_id != :statusVirus
            AND msr.status_id != :statusAuthorized
            AND msr.status_id != :statusRestored
            AND mr.domain = :domain
            AND ms.email = :emailSender
        SQL;
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('deliveryPass', DeliveryStatus::PASS);
        $stmt->bindValue('statusVirus', MessageStatus::VIRUS);
        $stmt->bindValue('statusAuthorized', MessageStatus::AUTHORIZED);
        $stmt->bindValue('statusRestored', MessageStatus::RESTORED);
        $stmt->bindValue('domain', $domain);
        $stmt->bindValue('emailSender', $emailSender);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function findByEmailRecipient(string $email): Query
    {
        $query = $this->getEntityManager()->createQuery(<<<SQL
            SELECT mr
            FROM App\Entity\Msgrcpt mr
            LEFT JOIN App\Entity\Maddr maddr WITH maddr.id = mr.rid
            LEFT JOIN App\Entity\Msgs msgs WITH msgs.mailId = mr.mailId AND msgs.partitionTag = mr.partitionTag
            WHERE maddr.email = :email
            AND msgs.quarType != ''
            SQL);

        $query->setParameter('email', $email);

        return $query;
    }

    public function consolidateStatus(): int
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
                WHEN mr.ds != :ds_pass AND mr.content != :content_virus AND mr.bl != 'Y' AND mr.bspam_level > d.level THEN :status_spam
                WHEN mr.ds != :ds_pass AND mr.content != :content_virus AND mr.bl != 'Y' AND mr.bspam_level <= d.level THEN :status_untreated
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
        $statement->bindValue('status_spam', MessageStatus::SPAMMED);
        $statement->bindValue('status_untreated', MessageStatus::UNTREATED);

        $result = $statement->execute();

        return $result->rowCount();
    }
}
