<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\User;
use Doctrine\DBAL;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, private CacheInterface $cache)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByUid(string $uid): ?User
    {
        return $this->createQueryBuilder('u')
                        ->join('u.domain', 'd')
                        ->andWhere('u.uid = :uid')
                        ->andWhere('d.active=1')
                        ->setParameter('uid', $uid)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    public function findOneByLdapDN(string $dn): ?User
    {
        return $this->createQueryBuilder('u')
                        ->join('u.domain', 'd')
                        ->andWhere('u.ldapDN = :ldapDN')
                        ->setParameter('ldapDN', $dn)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
                        ->join('u.domain', 'd')
                        ->andWhere('u.email = :email')
                        ->andWhere('d.active=1')
                        ->setParameter('email', $email)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    public function findOneByPrincipalName(string $principalName): ?User
    {
        return $this->createQueryBuilder('u')
                        ->join('u.domain', 'd')
                        ->andWhere('u.office365PrincipalName = :principalName')
                        ->andWhere('d.active=1')
                        ->setParameter('principalName', $principalName)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * @return User[]
     */
    public function findAllWithoutHumanAuthentication(): array
    {
        return $this->findBy([
            'bypassHumanAuth' => true,
        ]);
    }

    /**
     * Return all users from active domains
     *
     * @return array<int, array<string, int>>
     */
    public function activeUsers(bool $withAlias = false): array
    {
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
     * Return a paginable Doctrine Query to search users.
     *
     * Note that if you pass `isAlias: true`, you shouldn't pass any role as
     * aliases don't always have one. If you pass both, results may be
     * incoherent.
     *
     * @param array<string> $roles
     */
    public function search(
        User $currentUser,
        ?array $roles = null,
        ?string $searchKey = null,
        bool $isAlias = false
    ): ?Query {

        $qb = $this->createQueryBuilder('u')
                ->select('u')
                ->leftJoin('u.domain', 'd')
                ->leftJoin('u.policy', 'p');

        if ($isAlias) {
            $qb->leftJoin('u.originalUser', 'ou');
            $qb->where('u.originalUser is not null');
        } else {
            $qb->where('u.originalUser is null');
        }

        if ($roles) {
            $expr = new Expr\Orx();
            foreach ($roles as $key => $role) {
                $expr->add("u.roles LIKE :role{$key}");
                $qb->setParameter("role{$key}", "%\"{$role}\"%");
            }
            $qb->andWhere($expr);
        }

        if (!$currentUser->isSuperAdmin()) {
            $domains = $currentUser->getDomains()->toArray();
            if ($currentUser->getDomain()) {
                $domains[] = $currentUser->getDomain();
            }

            if (empty($domains)) {
                throw new AccessDeniedException('You do not have access to any domain');
            }

            $qb->andWhere('u.domain in (:domains)');
            $qb->setParameter('domains', $domains);
        }

        if ($searchKey) {
            $qb->andWhere('u.email LIKE :searchKey or u.fullname LIKE :searchKey or u.username LIKE :searchKey');
            $qb->setParameter('searchKey', "%{$searchKey}%");
        }

        $qb->orderBy('u.email', 'ASC');

        $result = $qb->getQuery();
        return $result;
    }

    /**
     * autocomplete query
     * @param Domain[] $allowedomains
     *
     * @return array<int, array<string, mixed>>
     */
    public function autocomplete(
        ?string $q,
        int $pageLimit = 30,
        ?int $page = null,
        array $allowedomains = [],
    ): array {
        $dql = $this->createQueryBuilder('u')
                ->select('u.id, u.email')
                ->andWhere(" u.originalUser is null")// filter only email user without alias
                ->andWhere(" u.roles !='[\"ROLE_SUPER_ADMIN\"]'")// filter only email user without alias
                ->andWhere(" u.roles !='[\"ROLE_ADMIN\"]'")
                ->andWhere(" u.email NOT LIKE '@%' ")// filter only email user without domain
        ;

        if ($allowedomains) {
            $dql->andWhere('u.domain in (:domains)');
            $dql->setParameter('domains', $allowedomains);
        }

        if ($q) {
            $dql->andWhere('u.email LIKE :query');
            $dql->setParameter('query', "%{$q}%");
        }

        $query = $dql->getQuery();
        $query->setMaxResults($pageLimit);

        return $query->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUsersWithRoleAndMessageCounts(User $user, ?Domain $domain = null): array
    {
        $domainId = $domain ? $domain->getId() : 'all';
        $userId = $user->getId();
        $cacheKey = "stats_users_messages_{$userId}_{$domainId}";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($user, $domain) {
            $item->expiresAfter(3600);

            $conn = $this->getEntityManager()->getConnection();
            $parameters = [];
            $types = [];

            $sql = <<<SQL
            SELECT
                u.email,
                u.fullname,
                d.id AS domain_id,
                d.domain AS domain,
                msgCounts.msgCount,
                msgCounts.msgBlockedCount,
                (
                    outMsgCounts.outMsgCount +
                    COALESCE(sqlLimitReportCounts.sqlLimitReportCount, 0)
                ) AS outMsgCount,
                (
                    outMsgCounts.outMsgBlockedCount +
                    COALESCE(sqlLimitReportCounts.sqlLimitReportCount, 0)
                ) AS outMsgBlockedCount
            FROM users u
            LEFT JOIN domain d ON u.domain_id = d.id
            LEFT JOIN (
                SELECT
                    om.from_addr,
                    COUNT(DISTINCT om.mail_id) AS outMsgCount,
                    COUNT(DISTINCT om.mail_id) - SUM(CASE
                        WHEN (
                            om.status_id = 2
                            OR om.content = 'C'
                            OR (om.status_id IS NULL AND om.spam_level < d.level AND om.content NOT IN ('C', 'V'))
                        ) THEN 1
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
            WHERE u.roles LIKE '%"ROLE_USER"%'
        SQL;

            if ($domain !== null) {
                $sql .= ' AND u.domain_id = :domain';
                $parameters['domain'] = $domain->getId();
                $types['domain'] = DBAL\ParameterType::INTEGER;
            }

            // if $user is an admin, add a condition to check only the domains he administer
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $domains = $user->getDomains()->toArray();
                if ($user->getDomain()) {
                    $domains[] = $user->getDomain();
                }

                if (empty($domains)) {
                    return [];
                }

                $sql .= ' AND u.domain_id in (:domains) ';
                $parameters['domains'] = array_map(function ($domain) {
                    return $domain->getId();
                }, $domains);
                $types['domains'] = DBAL\ArrayParameterType::INTEGER;
            }

            $sql .= <<<SQL
            GROUP BY u.id,
                outMsgCounts.outMsgCount,
                msgCounts.msgCount,
                outMsgCounts.outMsgBlockedCount,
                msgCounts.msgBlockedCount,
                sqlLimitReportCounts.sqlLimitReportCount
        SQL;

            return $conn->executeQuery($sql, $parameters, $types)->fetchAllAssociative();
        });
    }

    /**
     * @param string[] $emails
     * @return string[]
     */
    public function searchUsersByEmails(array $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder->select('u.email')
                ->where("u.email IN (:emails) and u.roles is not null")
                ->setParameter('emails', $emails);


        return $queryBuilder->getQuery()->getSingleColumnResult();
    }
}
