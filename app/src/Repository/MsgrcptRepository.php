<?php

namespace App\Repository;

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
    public function getAllMessageDomainRecipientsFromSender(string $emailSender, string $domain): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = <<<SQL
            SELECT m.*, mr.email as recept_mail, ms.email as sender_email,msr.rid FROM msgs m
            LEFT JOIN msgrcpt msr ON m.mail_id = msr.mail_id
            LEFT JOIN maddr ms ON ms.id = m.sid
            LEFT JOIN maddr mr ON mr.id = msr.rid
            WHERE m.content != "C"
            AND msr.content != "C"
            AND msr.status_id IS NULL
            AND mr.domain = :domain
            AND ms.email = :emailSender
        SQL;
        $stmt = $conn->prepare($sql);
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
}
