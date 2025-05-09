<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\Mailaddr;
use App\Entity\User;
use App\Entity\Wblist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Result;

/**
 * @extends ServiceEntityRepository<Wblist>
 */
class WblistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Wblist::class);
    }

    /**
     * @param ?array{
     *     sort: string,
     *     direction: string,
     * } $sortParams
     * @return array<int, array<string, mixed>>
     */
    public function search(?string $type = null, ?User $user = null, ?string $searchKey = null, ?array $sortParams = null): array {
        $dql = $this->createQueryBuilder('wb')
                ->select('u.id as rid, s.id as sid,wb.type as type,wb.priority as priority,wb.datemod, u.fullname, s.email as email,u.email as emailuser, g.name as group ')
                ->innerJoin('wb.rid', 'u')
                ->innerJoin('wb.sid', 's')
                ->leftJoin('wb.groups', 'g');

        if (in_array('ROLE_USER', $user->getRoles())) {
            $dql->andWhere('wb.rid = :user')
                    ->setParameter('user', $user);
        }

        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            $domainsIds = array_map(function ($entity) {
                return $entity->getId();
            }, $user->getDomains()->toArray());
            $dql->andWhere('u.domain in (:domains)')
                    ->setParameter('domains', $domainsIds);
        }

        if ($type) {
            $dql->andWhere('wb.wb = :type')
                    ->setParameter('type', $type);
        }

        if ($searchKey) {
            // Check if $user is an admin
            $isAdmin = $user && in_array('ROLE_ADMIN', $user->getRoles());
            if ($isAdmin) {
                $dql->andWhere('(LOWER(s.email) like LOWER(:searchkey) or LOWER(u.email) like LOWER(:searchkey) or LOWER(u.fullname) like LOWER(:searchkey))')
                    ->setParameter('searchkey', '%' . strtolower($searchKey) . '%');
            } else {
                $dql->andWhere('(LOWER(s.email) like LOWER(:searchkey))')
                    ->setParameter('searchkey', '%' . strtolower($searchKey) . '%');
            }
        }

        if ($sortParams) {
            $dql->orderBy($sortParams['sort'], $sortParams['direction']);
        }

        return $dql->getQuery()->getScalarResult();
    }

    /**
     * @return array<string, mixed>
     */
    public function searchByReceiptDomain(string $domain): array {
        $conn = $this->getEntityManager()->getConnection();
        $sql = " SELECT * FROM wblist  wb "
                . " LEFT JOIN mailaddr ma ON ma.id = wb.sid "
                . " LEFT JOIN users u ON wb.rid = u.id "
                . " WHERE u.email = '" . $domain . "' AND ma.email = '@.' ";
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery()->fetchAssociative();
    }

    public function deleteFromGroup(): Result {
        $conn = $this->getEntityManager()->getConnection();
        $sql = " DELETE FROM wblist "
                . " WHERE group_id  is not null";
        $stmt = $conn->prepare($sql);

        return $stmt->execute();
    }

    public function delete(int $rid, int $sid, int $priority): mixed {
        $qdl = $this->createQueryBuilder('wb')
                ->delete()
                ->where('wb.rid =:rid')
                ->andWhere('wb.sid =:sid')
                ->andWhere('wb.priority =:priority')
                ->setParameter('rid', $rid)
                ->setParameter('sid', $sid)
                ->setParameter('priority', $priority);

        return $qdl->getQuery()->execute();
    }

    public function insertFromGroup(): Result {
        $conn = $this->getEntityManager()->getConnection();
        $sqlSelectGroupwbList = "insert into wblist (rid, sid, group_id, wb, datemod, type, priority) 
                                    select u.id ,gw.sid, ug.groups_id, gw.wb, NOW(),'2',
                                    CASE g.override_user
                                          WHEN 1 THEN " . Wblist::WBLIST_PRIORITY_GROUP_OVERRIDE . " + g.priority" .
                " WHEN 0 THEN " . Wblist::WBLIST_PRIORITY_GROUP . " + g.priority
                                    END as 'priority'  from users u 
                                    inner join user_groups ug on ug.user_id =u.id
                                    inner join groups g on g.id =ug.groups_id 
                                    inner join groups_wblist gw on gw.group_id =g.id 
                                    where g.active = true and gw.wb !='' and g.priority is not null";

                
        $stmt = $conn->prepare($sqlSelectGroupwbList);
        return $stmt->execute();
    }

    /**
     * Get wblist informations about a sender adress
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWbListInfoForSender(string $senderAdress, string $recipientAdress): array {
        $infos = [];
        $s_str = '""';        
        $r_domain = explode('@', $recipientAdress)[1];
        $r_ext = explode('.', $r_domain)[1];
        $r_str = "'$recipientAdress','@$r_domain','@.$r_domain','@.$r_ext','@.'";

        if (!empty($senderAdress)) {
            $s_domain = explode('@', $senderAdress)[1];
            $s_ext = explode('.', $s_domain)[1];

            $s_str = "'$senderAdress','@$s_domain','@.$s_domain','@.$s_ext','@.'";
        }


        $conn = $this->getEntityManager()->getConnection();
        $sql_select_policy = 'SELECT *,users.id' .
                ' FROM users LEFT JOIN policy ON users.policy_id=policy.id' .
                ' WHERE users.email IN (' . $r_str . ') ORDER BY users.priority DESC ';

        $stmt = $conn->prepare($sql_select_policy);
        $result = $stmt->executeQuery()->fetchAllAssociative();
        foreach ($result as $row) {
            $id = $row['id'];
            $sql_select_white_black_list = 'SELECT wb,wblist.priority,wblist.datemod,wblist.group_id, wblist.sid, wblist.rid ' .
                    ' FROM wblist JOIN mailaddr ON wblist.sid=mailaddr.id' .
                    ' WHERE wblist.rid=' . $id . ' AND mailaddr.email IN (' . $s_str . ')' .
                    ' ';

            $sql_select_white_black_list .= ' ORDER BY wblist.priority DESC , mailaddr.priority DESC ';
            $stmt = $conn->prepare($sql_select_white_black_list);
            $result1 = $stmt->executeQuery()->fetchAllAssociative();
            foreach ($result1 as $row1) {
                $group = null;
                if (!is_null($row1['group_id'])) {
                    $group = $this->getEntityManager()->getRepository(Groups::class)->find($row1['group_id']);
                }
                $sender = $this->getEntityManager()->getRepository(Mailaddr::class)->find($row1['sid']);
                $recipient = $this->getEntityManager()->getRepository(User::class)->find($row1['rid']);

                $seconds = 0;

                $infos[] = [
                    'id' => $id,
                    'wb' => $row1['wb'],
                    'priority' => $row1['priority'],
                    'group' => $group,
                    'sender' => $sender,
                    'recipient' => $recipient,
                    'datemod' => $row1['datemod']
                ];
            }
        }

        return $infos;
    }

    /**
     * Return the default Wb for a domain
     */
    public function getDefaultDomainWBList(Domain $domain): ?string {
        $sid = $this->getEntityManager()->getRepository(Mailaddr::class)->findOneBy(['email' => '@.']);
        $rid = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => '@' . $domain->getDomain()]);
        $wb = $this->findOneBy(['rid' => $rid, 'sid' => $sid]);
        return $wb ? $wb->getWb() : null;
    }

    /**
     * Verify if a wblist rule exists for $user from $mailaddr
     */
    public function getOneByUser(User $user, Mailaddr $mailaddr): ?Wblist {
        $dql = $this->createQueryBuilder('wb')
                ->select('wb')
                ->where('wb.rid = :user')
                ->andWhere('wb.sid = :sender')
                ->setParameter('user', $user)
                ->setParameter('sender', $mailaddr);

        $query = $dql->getQuery();
        return $query->getOneOrNullResult();
    }

    /**
     * Check if wblist is overriden by anotherOne with highter priority
     *
     * @param array<string, mixed> $wbInfo
     */
    public function wbListIsOverriden(array $wbInfo): bool {
        $dql = $this->createQueryBuilder('wb')
                ->select('wb')
                ->where('wb.rid =:rid')
                ->andWhere('wb.sid =:sid')
                ->andWhere('wb.priority > :priority')
                ->setParameter('rid', $wbInfo['rid'])
                ->setParameter('sid', $wbInfo['sid'])
                ->setParameter('priority', $wbInfo['priority'])
                ->setMaxResults(1);

        $query = $dql->getQuery();
        $result = $query->getOneOrNullResult();
        return !is_null($result);
    }
}
