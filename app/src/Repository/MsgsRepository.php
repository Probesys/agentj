<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\MessageStatus;
use App\Entity\Msgs;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Msgs|null find($id, $lockMode = null, $lockVersion = null)
 * @method Msgs|null findOneBy(array $criteria, array $orderBy = null)
 * @method Msgs[]    findAll()
 * @method Msgs[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
     * @param type $type
     * @param type $alias
     * @return string
     */
    private function getSearchMsgSqlWhere(?User $user = null, $type = null, $alias = [], $fromDate = null)
    {
        $email = null;
        $sqlWhere = ' WHERE d.active=1  '; // and mr.content != "C" AND mr.content != "Y" AND mr.bl = "N" AND mr.bl != "V" AND mr.wl = "N" ';
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
                    $sqlWhere .= ' and (mr.status_id=1 or mr.bl = "Y")  and mr.content != "V"  ';
                    break;
                case MessageStatus::AUTHORIZED:
                  //$sqlWhere .= ' and (mr.status_id=2 or (mr.wl = "Y" and mr.status_id != 3)) ';
                    $sqlWhere .= ' and (mr.status_id=2 or mr.wl = "Y") and mr.content != "V" ';
                    break;
                case MessageStatus::DELETED:
                    $sqlWhere .= ' and mr.status_id=3 and mr.content != "V"  ';
                    break;
                case MessageStatus::RESTORED:
                    $sqlWhere .= ' and mr.status_id=5 and mr.content != "V"  ';
                    break;
                case 'All':
                    $sqlWhere .= ' ';
                    break;
                default:
                    $sqlWhere .= ' and bspam_level <= d.level and mr.content != "C" and mr.content != "V" and  mr.status_id=' . $type .  ' ';
                    break;
            }
        } else {
            $sqlWhere .= ' and mr.content != "C"  and mr.content != "V" AND mr.wl != "Y" AND mr.bl != "Y"  and ( mr.status_id IS NULL  OR mr.status_id = 4 ) and bspam_level <= d.level ';
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
     * @param type $type
     * @param type $alias
     * @return int
     */
    public function countByType(?User $user = null, $type = null, $alias = [])
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'select count(m.mail_id) as nb_result from msgs m '
            . 'LEFT JOIN msgrcpt mr ON m.mail_id = mr.mail_id '
            . 'LEFT JOIN maddr ON maddr.id = mr.rid '
            . ' LEFT JOIN message_status ms ON m.status_id = ms.id '
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
     * Count the number of message by $type (banned, etc..)
     * @param type $type
     * @param type $alias
     * @return int
     */
    public function countByTypeAndDays(?User $user = null, $type = null, $alias = [], ?\DateTime $day = null, ?Domain $domain = null)
    {
        $conn = $this->getEntityManager()->getConnection();


        $sql = 'select count(m.mail_id) as nb_result,m.time_iso  from msgs m '
            . 'LEFT JOIN msgrcpt mr ON m.mail_id = mr.mail_id '
            . 'LEFT JOIN maddr ON maddr.id = mr.rid '
            . ' LEFT JOIN message_status ms ON m.status_id = ms.id '
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
   * @param type $type
   * @return type
   */
    public function search(?User $user = null, $type = null, $alias = [], $searchKey = null, $sortPrams = null, $fromDate = null, $limit = null)
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
            $isAdmin = $user && in_array('ROLE_ADMIN', $user->getRoles());
            if ($isAdmin) {
                $sql .= ' AND (m.subject LIKE :searchKey OR maddr.email LIKE :searchKey OR m.from_addr LIKE :searchKey) ';
            } else {
                $sql .= ' AND (m.subject LIKE :searchKey OR m.from_addr LIKE :searchKey) ';
            }
        }


        if ($sortPrams) {
            $sql .= ' ORDER BY ' . $sortPrams['sort'] . ' ' . $sortPrams['direction'];
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

public function advancedSearch(?User $user = null, string $messageType = 'incoming', $searchKey = null, $sortParams = null)
{
    $conn = $this->getEntityManager()->getConnection();
    $table = $messageType === 'outgoing' ? 'out_msgs' : 'msgs';
    $msgrcptTable = $messageType === 'outgoing' ? 'out_msgrcpt' : 'msgrcpt';

    $sql = "
        SELECT
            m.*,
            mr.status_id,
            ms.name,
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
        LEFT JOIN message_status ms ON mr.status_id = ms.id
        LEFT JOIN users u ON u.email = maddr.email
        LEFT JOIN domain d ON u.domain_id = d.id
        WHERE d.active = 1
    ";

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
   * SELECT only msgs to send email with captch
   * @return type
   */
    public function searchMsgsToSendAuthToken()
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT m.mail_id,maddr_sender.email as from_addr,m.send_captcha,maddr.email,m.partition_tag,m.secret_id,mr.rid FROM msgs m '
            . ' LEFT JOIN msgrcpt mr ON m.mail_id = mr.mail_id '
            . ' LEFT JOIN maddr ON maddr.id = mr.rid '
            . ' LEFT JOIN maddr maddr_sender ON maddr_sender.id = m.sid '
            . ' LEFT JOIN users u on u.email=maddr.email'
            . ' LEFT JOIN domain d on d.id=u.domain_id'
            . ' LEFT JOIN message_status ms ON m.status_id = ms.id '
            . ' WHERE (m.is_mlist is null or m.is_mlist=0) and m.status_id is null and mr.send_captcha=0 and m.content != "C" AND m.content != "V"  AND mr.bspam_level < d.level AND maddr.is_invalid is null '
            . ' AND mr.wl != "Y" and mr.bl != "Y"  and mr.status_id IS NULL and mr.content != "C" AND mr.content != "V" '
             . '  GROUP BY email,sid';
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

  /**
   * Update status of all message for one sender
   * @param type $emailSender
   * @param type $emailRecipient
   * @param type $status
   */
    public function updateMessageSender($emailSender, $emailRecipient, $status)
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
   * @param type $partitiontag
   * @param type $mailId
   * @param type $status
   */
    public function changeStatus($partitiontag, $mailId, $status)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'UPDATE msgs SET status_id =  ' . $status . '  WHERE partition_tag = "' . $partitiontag . '" AND mail_id = "' . $mailId . '"';
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery();
    }

  /**
   * return the number of msg are already processing to send authetification request for email "to", request without the mailId
   * @param type $to
   * @param type $from
   * @param type $mailId
   * @return type
   */
    public function checkLastRequestSent($to, $from)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT m.time_iso FROM msgs m '
            . ' LEFT JOIN msgrcpt mr ON m.mail_id = mr.mail_id '
            . ' LEFT JOIN maddr ON maddr.id = mr.rid '
            . ' LEFT JOIN maddr maddr_sender ON maddr_sender.id = m.sid '
            . ' LEFT JOIN message_status ms ON m.status_id = ms.id '
            . ' WHERE maddr.email = "' . $to . '"  AND maddr_sender.email = :from_addr AND mr.send_captcha !=0 order by m.time_iso desc limit 1';

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':from_addr', $from);
        if (!$from) {
            return [];
        }

        return $stmt->executeQuery()->fetchAllAssociative();
    }

  /**
   * Update the maddr adresses that have a message status error
   */
    public function updateErrorStatus()
    {
        $conn = $this->getEntityManager()->getConnection();
      //update the email Maddr
        $sql = "UPDATE maddr SET is_invalid = 1 WHERE id in ( select sid from msgs where status_id = 4)";
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery();
    }

  /**
   * Get all message from emailSender and rid of receipt with status is null and not clean (content != C)
   * @todo chercher le content = spammy et le rajouter dans le where !=
   * @param type $emailSender
   * @param type $emailRecipient
   * @return type
   */
    public function getAllMessageRecipient($emailSender, $emailRecipient)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT m.*, mr.email as recept_mail, ms.email as sender_email,msr.rid FROM msgs m'
            . ' LEFT JOIN msgrcpt msr ON m.mail_id = msr.mail_id '
            . ' LEFT JOIN maddr ms ON ms.id = m.sid '//sid is sender
            . ' LEFT JOIN maddr mr ON mr.id = msr.rid '//rid is recipient
            . ' WHERE m.content != "C" AND msr.content != "C"  AND mr.email = "' . $emailRecipient . '" AND ms.email =  "' . $emailSender . '"';
    $stmt = $conn->prepare($sql);
//    $stmt->execute();
    return $stmt->executeQuery()->fetchAllAssociative();
  }

  /**
   * Return message statistics on a period
   * @param type $emailReceipient
   * @param type $start
   * @param type $end
   * @return type
   */
    public function getAllMessageReceipientForReport($emailReceipient = null, $start = null, $end = null)
    {
      //todo prévoir par domaine rajouter une jointure pour les admins

        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT  m.content, mt.name,count(*) as nb FROM msgs m'
            . ' LEFT JOIN msgrcpt mr ON m.mail_id = mr.mail_id '
            . ' LEFT JOIN maddr ON maddr.id = mr.rid '
            . ' LEFT JOIN message_status mt on mt.id = m.status_id'
            . ' WHERE 1  ';
        if ($emailReceipient) {
            $sql .= ' AND maddr.email = "' . $emailReceipient . '" ';
        }
        if ($start) {
            $sql .= ' AND time_num >= ' . $start;
        }
        if ($end) {
            $sql .= ' AND time_num <= ' . $end;
        }
        $sql .= ' GROUP BY m.content, m.status_id';

        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

  /**
   * Messages wait to unblock
   * @return type
   */
    public function getMsgsToTreat()
    {
      //todo prévoir par domaine rajouter une jointure pour les admins

        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT  count(*) as nb, maddr.email FROM msgs m'
            . ' LEFT JOIN msgrcpt mr ON m.mail_id = mr.mail_id '
            . ' LEFT JOIN maddr ON maddr.id = mr.rid '
            . ' WHERE m.content != "C" AND m.content != "Y" AND mr.bl = "N" AND mr.wl = "N" AND mr.status_id IS NULL  ';

        $sql .= ' GROUP BY maddr.email';

        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }


  /**
   * Delete message older $date
   * @param timestamp $date
   * */
    public function truncateMessageOlder($date)
    {
        if (!is_null($date)) {
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
            $nbDeletedQuantaine = $result->rowCount();

            
            
            $sql2 = ' DELETE FROM msgs WHERE time_num < ' . $date;
            $stmt2 = $conn->prepare($sql2);
            $result = $stmt2->executeQuery();
            $nbDeletedMsgs = $result->rowCount();
            return ['nbDeletedMsgs' => $nbDeletedMsgs, 'nbDeletedMsgrcpt' => $nbDeletedMsgrcpt, 'nbDeletedQuantaine' => $nbDeletedQuantaine];
        }
    }
}
