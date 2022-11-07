<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\Mailaddr;
use App\Entity\User;
use App\Entity\Wblist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WblistRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Wblist::class);
    }

    /**
     * search query
     * @param type $type
     * @return type
     */
    public function search($type = null, User $user = null, $searchKey = null, $sortPrams = null) {

        $dql = $this->createQueryBuilder('wb')
                ->select('u.id as rid, s.id as sid,wb.type as type,wb.datemod, u.fullname, s.email as email,u.email as emailuser, g.name as group ')
                ->join('wb.rid', 'u')
                ->join('wb.sid', 's')
                ->leftJoin('wb.groups','g');

        $conn = $this->getEntityManager()->getConnection();
//        dd($user->getRoles());
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
            $dql->andWhere('(s.email like :searchkey or u.email like :searchkey or u.fullname like :searchkey )')
                    ->setParameter('searchkey', $searchKey);                
        }
        
        if ($sortPrams) {
            $dql->orderBy($sortPrams['sort'], $sortPrams['direction']);
//            $sql .= ' ORDER BY ' . $sortPrams['sort'] . ' ' . $sortPrams['direction'];
        } else {
//            $sql .= ' ORDER BY wb.datemod desc ';
        }
        $query = $dql->getQuery()->getResult();

        return $query;

//        if ($sortPrams) {
//            $sql .= ' ORDER BY ' . $sortPrams['sort'] . ' ' . $sortPrams['direction'];
//        } else {
//            $sql .= ' ORDER BY wb.datemod desc ';
//        }


//        $stmt = $conn->prepare($sql);
//        $stmt->execute();

//        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * search query
     * @param type $type
     * @return type
     */
    public function searchByReceiptDomain($domain) {

        $conn = $this->getEntityManager()->getConnection();
        $sql = " SELECT * FROM wblist  wb "
                . " LEFT JOIN mailaddr ma ON ma.id = wb.sid "
                . " LEFT JOIN users u ON wb.rid = u.id "
                . " WHERE u.email = '" . $domain . "' AND ma.email = '@.' ";
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAssociative();
    }

    /**
     * delete query
     * @param int $groupId
     * @return type
     */
    public function deleteFromGroup($groupId) {

        $conn = $this->getEntityManager()->getConnection();
        $sql = " DELETE FROM wblist "
                . " WHERE group_id = '" . $groupId . "'";
        $stmt = $conn->prepare($sql);

        return $stmt->execute();
    }

    /**
     * Delete wblist entries for a group and user
     * @param Group $group
     * @param User $user
     * @return mixed
     */
    public function deleteUserWbListFromGroup(Groups $group, User $user) {
        $qdl = $this->createQueryBuilder('wb')
                ->delete()
                ->where('wb.groups = :group')
                ->andWhere('wb.rid =:user')
                ->setParameter('group', $group)
                ->setParameter('user', $user);

        return $qdl->getQuery()->execute();
    }

    /**
     * delete query
     * @param int $groupId
     * @return type
     */
    public function delete($rid, $sid) {

        $conn = $this->getEntityManager()->getConnection();
        $sql = " DELETE FROM wblist "
                . " WHERE rid = '" . $rid . "' and sid='" . $sid . "'";
        $stmt = $conn->prepare($sql);

        return $stmt->execute();
    }

    /**
     * insert new rules for user of a group
     * @param int $groupId
     * @return type
     */
    public function insertFromGroup($groupId) {
        $baseAddr = $this->getEntityManager()->getRepository(Mailaddr::class)->findOneBy(['email' => '@.']);
        /* @var $group Groups */
        $group = $this->getEntityManager()->getRepository(Groups::class)->findOneBy(['id' => $groupId]);
        $priority = Wblist::WBLIST_PRIORITY_GROUP;
        if ($group->getOverrideUser()) {
            $priority = Wblist::WBLIST_PRIORITY_GROUP_OVERRIDE;
        }

        $conn = $this->getEntityManager()->getConnection();
        $sqlSelectGroupwbList = "SELECT u.id as rid , '" . $baseAddr->getId() . "' as sid, g.wb,2,g.id," . $priority . " FROM groups g 
            left join users u ON u.groups_id = g.id 
            WHERE g.wb is not null and g.wb != ''  and u.id IS NOT NULL AND g.id =  " . $groupId . "
            UNION
            SELECT u.id as rid, gw.sid as sid,gw.wb as wb,2,gw.group_id as id, " . $priority . "
            from users u
            left join groups_wblist gw on gw.group_id=u.groups_id WHERE gw.wb is not null and gw.wb != '' and u.id IS NOT NULL AND gw.group_id =" . $groupId;

        $stmt = $conn->prepare($sqlSelectGroupwbList);
//        $stmt->execute();
        $result = $stmt->executeQuery()->fetchAllAssociative();
        foreach ($result as $row) {
            $wblist = $this->findOneBy(['rid' => $row['rid'], 'sid' => $row['sid']]);
            if ($wblist) {
                $wblist->setGroups($group);
                $wblist->setPriority($priority);
            } else {
                $user = $this->getEntityManager()->getRepository(User::class)->find($row['rid']);
                $mailAddr = $this->getEntityManager()->getRepository(Mailaddr::class)->find($row['sid']);
                $wblist = new Wblist($user, $mailAddr);
                $wblist->setGroups($group);
                $wblist->setPriority($priority);
                $wblist->setType(2);
                $wblist->setWb($row['wb']);
            }
            $this->getEntityManager()->persist($wblist);
        }
        $this->getEntityManager()->flush();
    }

    /**
     * Get wblist informations about a sender adress
     * @param type $senderAdress
     * @return type
     */
    public function getWbListInfoForSender($senderAdress, $recipientAdress, $strDateMsg = null) {

        $infos = [];
        $s_str = '""';
        $dateMsg = $strDateMsg ? new \DateTime($strDateMsg) : null;
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
//dd($sql_select_policy);
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
//            $stmt->execute();
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
     * insert wblist from user to alias when alias is created
     * @return type
     */
    public function importWbListFromUserToAlias(User $alias) {

        $originalUser = $alias->getOriginalUser();
        $wbLists = $this->findBy(['rid' => $originalUser->getId()]);
        foreach ($wbLists as $wblist) {
            /* @var $newWbLis Wblist */
            $newWbLis = clone $wblist;
            $newWbLis->setDatemod(new \DateTime());
            $newWbLis->setRid($alias);
            $this->getEntityManager()->persist($newWbLis);
        }
        $this->getEntityManager()->flush();
    }

    /**
     * Return the default Wb for a domain
     * @return type
     */
    public function getDefaultDomainWBList(Domain $domain) {
        $sid = $this->getEntityManager()->getRepository(Mailaddr::class)->findOneBy(['email' => '@.']);
        $rid = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => '@' . $domain->getDomain()]);

        $wb = $this->findOneBy(['rid' => $rid, 'sid' => $sid]);
        return $wb ? $wb->getWb() : null;
    }

    /**
     * Verify if a wblist rule exists for $user from $mailaddr
     * @param User $user
     * @param Mailaddr $mailaddr
     * @return bool
     */
    public function getOneByUser(User $user, Mailaddr $mailaddr): ?Wblist {
        $dql = $this->createQueryBuilder('wb')
                ->select('wb')
                ->where('wb.rid = :user')
                ->andWhere('wb.sid = :sender')
                ->setParameter('user', $user)
                ->setParameter('sender', $mailaddr);

        $query = $dql->getQuery();
        $result = $query->getOneOrNullResult();
        return $query->getOneOrNullResult();
    }

}
