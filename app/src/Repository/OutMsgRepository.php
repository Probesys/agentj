<?php

namespace App\Repository;

use App\Entity\OutMsg;
use Doctrine\DBAL;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseMessageRepository<OutMsg>
 */
class OutMsgRepository extends BaseMessageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutMsg::class);
    }

    /**
     * Delete outgoing messages older than $date
     *
     * @return array{
     *     nbDeletedMsgs: int,
     *     nbDeletedQuarantine: int,
     * }
     */
    public function truncateMessageOlder(int $date): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = ' DELETE q FROM out_quarantine q '
            . ' LEFT JOIN  out_msgs m ON m.mail_id = q.mail_id '
            . ' WHERE m.time_num < :date';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('date', $date, DBAL\ParameterType::INTEGER);
        $result = $stmt->executeQuery();
        $nbDeletedQuarantine = $result->rowCount();

        $sql = ' DELETE FROM out_msgs WHERE time_num < :date';

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
