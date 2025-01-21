<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\Mailaddr;
use App\Entity\User;
use App\Entity\Msgs;
use App\Entity\OutMsgs;
use App\Entity\Wblist;
use Cocur\Slugify\Slugify;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, User::class);
    }

    public function findOneByUid(string $uid): ?User {
        return $this->createQueryBuilder('u')
                        ->join('u.domain', 'd')
                        ->andWhere('u.uid = :uid')
                        ->andWhere('d.active=1')
                        ->setParameter('uid', $uid)
                        ->getQuery()
                        ->getOneOrNullResult();
    }
    
    public function findOneByLdapDN(string $dn): ?User {
        return $this->createQueryBuilder('u')
                        ->join('u.domain', 'd')
                        ->andWhere('u.ldapDN = :ldapDN')
                        ->setParameter('ldapDN', $dn)
                        ->getQuery()
                        ->getOneOrNullResult();
    }    

    public function findOneByEmail(string $email): ?User {
        return $this->createQueryBuilder('u')
                        ->join('u.domain', 'd')
                        ->andWhere('u.email = :email')
                        ->andWhere('d.active=1')
                        ->setParameter('email', $email)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    public function findOneByPrincipalName(string $principalName): ?User {
        return $this->createQueryBuilder('u')
                        ->join('u.domain', 'd')
                        ->andWhere('u.office365PrincipalName = :principalName')
                        ->andWhere('d.active=1')
                        ->setParameter('principalName', $principalName)
                        ->getQuery()
                        ->getOneOrNullResult();
    }    

    /**
     * Return all users from active domains
     * @param boolean $withAlias
     * @return type
     */
    public function activeUsers($withAlias = false) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT u.id from users u
            inner join domain d on u.domain_id=d.id
            where d.active=1 and u.roles="[\"ROLE_USER\"]"';
        if (!$withAlias) {
            $sql .= " and u.original_user_id is null ";
        }

        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * search query
     * @param type $login
     * @return type
     */
    public function search($login) {

        $login = strtolower($login);
        $dql = $this->createQueryBuilder('a')
                ->select('a')
        ;

        if (isset($login) && $login != '') {
            $dql->andwhere('a.email = \'' . $login . '\'');
        }

        return $dql->getQuery();
    }

    /**
     * search query by role
     * @param type $roles
     * @return type
     */
    public function searchByRole(User $user, $role = null) {

        $dql = $this->createQueryBuilder('u')
                ->select('u.id, u.email, u.fullname, u.username, u.roles, u.imapLogin', 'p.policyName', 'd.domain')
                ->leftJoin('u.domain', 'd')
                ->join('u.policy', 'p')
                ->where('u.originalUser is null');

        if ($role) {
            $dql->andWhere('u.roles = :role');
            $dql->setParameter('role', $role);
        }

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
            $dql->andWhere('u.domain in (' . implode(',', $domainsIds) . ')');
        }

        $result = $dql->getQuery()->execute();
        return $result;
    }

    public function getListAliases(User $user): ?array {
        $dql = $this->createQueryBuilder('u')
                ->join('u.originalUser', 'a')
                ->where('u.originalUser= :user')
                ->setParameter('user', $user);

        $query = $dql->getQuery();
        return $query->getResult();
    }

    /**
     * Return a list of aliases associate to a user
     * @return type
     */
    public function searchAlias(User $user) {
        $dql = $this->createQueryBuilder('u')
                ->select('u.id, u.email as alias, a.email as email')
                ->join('u.originalUser', 'a')
                ->where('u.originalUser is not null');

        //todo finir les droits sur les domaines
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
            $dql->andWhere('u.domain in (' . implode(',', $domainsIds) . ')');
        }


        return $dql->getQuery()->getScalarResult();
    }

    /**
     *  autocomplete query
     * @param type $q
     * @param type $all
     * @return type
     */
    public function autocomplete($q, $page_limit = 30, $page = null, $allowedomains = null) {
        //    $slugify = new Slugify();
        $dql = $this->createQueryBuilder('u')
                ->select('u.id, u.email')
                ->andWhere(" u.originalUser is null")// filter only email user without alias
                ->andWhere(" u.roles !='[\"ROLE_SUPER_ADMIN\"]'")// filter only email user without alias
                ->andWhere(" u.roles !='[\"ROLE_ADMIN\"]'")
                ->andWhere(" u.email NOT LIKE '@%' ")// filter only email user without domain
        ;

        if ($allowedomains) {
            $domainsIds = array_map(function ($domain) {
                return $domain->getId();
            }, $allowedomains);
            $dql->andWhere("u.domain in (" . implode(',', $domainsIds) . ")");
        }

        if ($q) {
            $dql->andWhere("u.email LIKE '%" . $q . "%'");
        }
        $query = $dql->getQuery();
        $query->setMaxResults($page_limit);

        if ($page) {
            $query->setFirstResult(($page - 1) * $page_limit);
        }
        return $query->getResult();
    }

    /**
     * Search users in list of domains
     * @param type $domains
     * @param type $q
     * @param type $page_limit
     * @param type $page
     * @return type
     */
    public function searchInDomains($domain, $q, $page_limit = 30, $page = null) {
        $slugify = new Slugify();
        $slug = $slugify->slugify($q, '-');

        $dql = $this->createQueryBuilder('u')
                ->select('u.id, u.email')
                ->where(" u.username is null")// filter only email user without user admin

        ;

        $dql->andWhere("u.domain = '" . $domain . "'");
        if ($q && strlen($q) >= 3) {
            $dql->andWhere("u.email LIKE '%" . $slug . "%'");
            $query = $dql->getQuery();
            $query->getSQL();
            $query->setMaxResults($page_limit);
            if ($page) {
                $query->setFirstResult(($page - 1) * $page_limit);
            }
            return $query->getResult();
        } else {
            return null;
        }
    }

    /**
     * Update the user policy from group policy
     * @param type $groupId
     * @return type
     */
    public function updatePolicyForGroup($groupId) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = " UPDATE users set policy_id = (SELECT policy_id FROM groups WHERE id = " . $groupId . " ) "
                . " WHERE groups_id =  " . $groupId
        ;
        $stmt = $conn->prepare($sql);
        return $stmt->execute();
    }

    /**
     * Update the user policy from domain policy
     * @param type $groupId
     * @return type
     */
    public function updatePolicyFromGroupToDomain($groupId) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = " UPDATE users  u "
                . "INNER JOIN groups g on g.id=u.groups_id "
                . "INNER JOIN domain d on d.id=u.domain_id "
                . " set u.policy_id =d.policy_id "
        ;
        $stmt = $conn->prepare($sql);
        return $stmt->execute();
    }

    /**
     * create a default wblist entry for the new user based on domain wblist
     * @param type $user
     */
    public function createDefaultWbListFromdomain(User $user) {

        $mailaddr = $this->getEntityManager()->getRepository(Mailaddr::class)->findOneBy((['email' => '@.']));
        $domainWblist = $this->getEntityManager()->getRepository(Domain::class)->getDomainWblist('@' . $user->getDomain(), $mailaddr->getId());
        $userDefaultWbList = $this->getEntityManager()->getRepository(Wblist::class)->findOneby(['rid' => $user, 'sid' => $mailaddr]);
        if (!$userDefaultWbList) {
            $userDefaultWbList = new Wblist($user, $mailaddr);
            $userDefaultWbList->setWb($domainWblist);
            $userDefaultWbList->setPriority(Wblist::WBLIST_PRIORITY_DOMAIN);
            $this->getEntityManager()->persist($userDefaultWbList);
            $this->getEntityManager()->flush();
        }
    }

    public function getGroupsWbListForUser(User $user) {
        $dql = $this->createQueryBuilder('u')
                ->select('u.id as rid ,gwl.wb,g.id as groupId,maddr.id as sid, g.priority, g.overrideUser')
                ->innerJoin('u.groups', 'g')
                ->innerJoin('g.groupsWbLists', 'gwl')
                ->innerJoin('gwl.mailaddr', 'maddr')
                ->where('g.active = true')
                ->andWhere('u.id= :user')
                ->setParameter('user', $user)
                ->orderBy('g.priority', 'desc');
        $query = $dql->getQuery();

        return $query->getScalarResult();
    }

    public function getWbListForUser(User $user, bool $excludeGroupWblist = false) {
        $dql = $this->createQueryBuilder('u')
                ->select('maddr.id ')
                ->innerJoin('u.wbLists', 'wb')
                ->innerJoin('wb.rid', 'maddr')
                ->andWhere('u.id= :user')
                ->setParameter('user', $user);
        if ($excludeGroupWblist) {
            $dql->andWhere('wb.groups is null');
        }
        $query = $dql->getQuery();

        return $query->getScalarResult();
    }

    public function getUsersWithRoleAndMessageCounts($domainId = null)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT
                u.email,
                u.fullname,
                d.id AS domain_id,
                d.domain AS domain,
                msgCounts.msgCount,
                msgCounts.msgBlockedCount,
                outMsgCounts.outMsgCount + COALESCE(sqlLimitReportCounts.sqlLimitReportCount, 0) AS outMsgCount,
                outMsgCounts.outMsgBlockedCount + COALESCE(sqlLimitReportCounts.sqlLimitReportCount, 0) AS outMsgBlockedCount
            FROM users u
            LEFT JOIN domain d ON u.domain_id = d.id
            LEFT JOIN (
                SELECT
                    om.from_addr,
                    COUNT(DISTINCT om.mail_id) AS outMsgCount,
                    COUNT(DISTINCT om.mail_id) - SUM(CASE
                        WHEN om.status_id = 2 OR om.content = \'C\' OR (om.status_id IS NULL AND om.spam_level < d.level AND om.content NOT IN (\'C\', \'V\')) THEN 1
                        ELSE 0
                    END) AS outMsgBlockedCount
                FROM out_msgs om
                LEFT JOIN users u ON om.from_addr = u.email
                LEFT JOIN domain d ON u.domain_id = d.id
                GROUP BY om.from_addr
            ) AS outMsgCounts ON outMsgCounts.from_addr = u.email
            LEFT JOIN (
                SELECT
                    ma.email,
                    COUNT(DISTINCT m.mail_id) AS msgCount,
                    COUNT(DISTINCT m.mail_id) - SUM(CASE WHEN m.quar_type = "" THEN 1 ELSE 0 END) AS msgBlockedCount
                FROM maddr ma
                LEFT JOIN msgrcpt mr ON ma.id = mr.rid
                LEFT JOIN msgs m ON mr.mail_id = m.mail_id
                GROUP BY ma.email
            ) AS msgCounts ON msgCounts.email = u.email
            LEFT JOIN (
                SELECT
                    slr.mail_id,
                    COUNT(slr.mail_id) AS sqlLimitReportCount
                FROM sql_limit_report slr
                GROUP BY slr.mail_id
            ) AS sqlLimitReportCounts ON sqlLimitReportCounts.mail_id = u.email
            WHERE u.roles LIKE :role
        ';

        if ($domainId !== null) {
            $sql .= ' AND u.domain_id = :domainId';
        }

        $sql .= ' GROUP BY u.id, outMsgCounts.outMsgCount, msgCounts.msgCount, outMsgCounts.outMsgBlockedCount, msgCounts.msgBlockedCount, sqlLimitReportCounts.sqlLimitReportCount';

        $stmt = $conn->prepare($sql);
        $params = ['role' => '%"ROLE_USER"%'];
        if ($domainId !== null) {
            $params['domainId'] = $domainId;
        }
        $result = $stmt->executeQuery($params)->fetchAllAssociative();

        return $result;
    }

}
