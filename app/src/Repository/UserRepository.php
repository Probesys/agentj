<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\User;
use Cocur\Slugify\Slugify;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

/**
 * @extends ServiceEntityRepository<User>
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
     *
     * @return array<int, array<string, int>>
     */
    public function activeUsers(bool $withAlias = false): array {
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
     */
    public function search(?string $login): Query {
        $login = strtolower($login ?? '');
        $dql = $this->createQueryBuilder('u')
                ->select('u')
        ;

        if ($login != '') {
            $dql->andwhere('u.email = \'' . $login . '\'');
        }

        return $dql->getQuery();
    }

    /**
     * search query by role
     *
     * @return User[]
     */
    public function searchByRole(User $user, ?string $role = null): array {

        $dql = $this->createQueryBuilder('u')
                ->select('u.id, u.email, u.fullname, u.username, u.roles, u.imapLogin', 'p.policyName', 'd.domain')
                ->leftJoin('u.domain', 'd')
                ->leftJoin('u.policy', 'p')
                ->where('u.originalUser is null');

        if ($role) {
            $dql->andWhere('u.roles = :role');
            $dql->setParameter('role', $role);
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
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
                return [];
            }
            $dql->andWhere('u.domain in (' . implode(',', $domainsIds) . ')');
        }

        $result = $dql->getQuery()->execute();
        return $result;
    }

    /**
     * @return User[]
     */
    public function getListAliases(User $user): array {
        $dql = $this->createQueryBuilder('u')
                ->join('u.originalUser', 'a')
                ->where('u.originalUser= :user')
                ->setParameter('user', $user);

        $query = $dql->getQuery();
        return $query->getResult();
    }

    /**
     * Return a list of aliases associate to a user
     * @return User[]
     */
    public function searchAlias(User $user): array {
        $dql = $this->createQueryBuilder('u')
                ->select('u.id, u.email as alias, a.email as email')
                ->join('u.originalUser', 'a')
                ->where('u.originalUser is not null');

        //todo finir les droits sur les domaines
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
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
                return [];
            }
            $dql->andWhere('u.domain in (' . implode(',', $domainsIds) . ')');
        }


        return $dql->getQuery()->getScalarResult();
    }

    /**
     * autocomplete query
     * @param ?Domain[] $allowedomains
     *
     * @return array<int, array<string, mixed>>
     */
    public function autocomplete(?string $q, int $page_limit = 30, ?int $page = null, ?array $allowedomains = null): array {
        //    $slugify = new Slugify();
        $dql = $this->createQueryBuilder('u')
                ->select('u.id, u.email')
                ->andWhere(" u.originalUser is null")// filter only email user without alias
                ->andWhere(" u.roles !='[\"ROLE_SUPER_ADMIN\"]'")// filter only email user without alias
                ->andWhere(" u.roles !='[\"ROLE_ADMIN\"]'")
                ->andWhere(" u.email NOT LIKE '@%' ")// filter only email user without domain
        ;

        if ($allowedomains) {
            $domainsIds = array_map(function (Domain$domain) {
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
            // $query->setFirstResult(($page - 1) * $page_limit);
        }

        return $query->getResult();
    }

    /**
     * @param int $domainId
     * @return array<int, array<string, mixed>>
     */
    public function getUsersWithRoleAndMessageCounts(User $user, ?int $domainId = null): array
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

        // if $user is an admin, add a condition to check only the domains he administer
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
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

            $sql .= ' AND u.domain_id in (' . implode(',', $domainsIds) . ') ';

            if ($domainsIds === []) {
                return [];
            }
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
