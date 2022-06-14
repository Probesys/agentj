<?php

namespace App\Repository;

use App\Entity\Msgrcpt;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Msgs|null find($id, $lockMode = null, $lockVersion = null)
 * @method Msgs|null findOneBy(array $criteria, array $orderBy = null)
 * @method Msgs[]    findAll()
 * @method Msgs[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MsgrcptRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Msgrcpt::class);
    }


  /**
   * Update the status of a message for one recipient
   * @param type $partitiontag
   * @param type $mailId
   *  * @param type $rid
   * @param type $status
   */
    public function changeStatus($partitiontag, $mailId, $status, $rid)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'UPDATE msgrcpt SET status_id =  ' . $status . '  WHERE partition_tag = "' . $partitiontag . '" AND mail_id = "' . $mailId . '" and rid=' . $rid ;
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }

  /**
   * Get all message from emailSender and rid of receipt with status is null and not clean (content != C)
   * @todo chercher le content = spammy et le rajouter dans le where !=
   * @param type $emailSender
   * @param type $emailRecipient
   * @return type
   */
    public function getAllMessageDomainRecipientsFromSender($emailSender, $domain)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT m.*, mr.email as recept_mail, ms.email as sender_email,msr.rid FROM msgs m'
            . ' LEFT JOIN msgrcpt msr ON m.mail_id = msr.mail_id '
            . ' LEFT JOIN maddr ms ON ms.id = m.sid '//sid is sender
            . ' LEFT JOIN maddr mr ON mr.id = msr.rid '//rid is recipient
            . ' WHERE m.content != "C" AND msr.content != "C" AND msr.status_id IS NULL AND mr.domain = "' . $domain . '" AND ms.email =  "' . $emailSender . '"';
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }
}
