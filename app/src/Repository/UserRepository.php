<?php

namespace App\Repository;

use App\Amavis\DeliveryStatus;
use App\Amavis\MessageStatus;
use App\Entity\Domain;
use App\Entity\Maddr;
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

    public function findOneByMailAddress(Maddr $maddr): ?User
    {
        $email = $maddr->getEmail();
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * @return User[]
     */
    public function findUserAndAliasesByMaddr(Maddr $maddr): array
    {
        $mainUser = $this->findOneByMailAddress($maddr);

        if (!$mainUser) {
            return [];
        }

        // Make sure to get the main user
        while ($mainUser->getOriginalUser()) {
            $mainUser = $mainUser->getOriginalUser();
        }

        $aliases = $mainUser->getAliases()->toArray();

        return array_merge([$mainUser], $aliases);
    }

    public function findDomainUser(string $domainName): ?User
    {
        return $this->findOneBy([
            'email' => '@' . $domainName,
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
    public function getUsersWithMessageCountsByDomain(Domain $domain): array
    {
        $cacheKey = "stats_users_messages_{$domain->getId()}";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($domain) {
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
                JOIN domain d ON u.domain_id = d.id
                LEFT JOIN (
                    SELECT
                        oma.email,
                        COUNT(omr.mail_id) AS outMsgCount,
                        COUNT(CASE
                            WHEN omr.ds <> :deliveryStatus THEN om.mail_id
                        END) AS outMsgBlockedCount
                    FROM out_msgrcpt omr
                    JOIN out_msgs om ON om.mail_id = omr.mail_id
                    JOIN maddr oma ON oma.id = om.sid
                    GROUP BY oma.email
                ) AS outMsgCounts ON outMsgCounts.email = u.email
                LEFT JOIN (
                    SELECT
                        ma.email,
                        COUNT(mr.mail_id) AS msgCount,
                        COUNT(CASE WHEN mr.status_id NOT IN (
                            :msgStatusAuthorized,
                            :msgStatusRestored
                        ) THEN ma.email END) AS msgBlockedCount
                    FROM msgrcpt mr
                    JOIN maddr ma ON ma.id = mr.rid
                    GROUP BY ma.email
                ) AS msgCounts ON msgCounts.email = u.email
                LEFT JOIN (
                    SELECT
                        slr.mail_id AS email,
                        COUNT(slr.mail_id) AS sqlLimitReportCount
                    FROM sql_limit_report slr
                    GROUP BY slr.mail_id
                ) AS sqlLimitReportCounts ON sqlLimitReportCounts.email = u.email
                WHERE u.roles LIKE '%"ROLE_USER"%'
                AND u.domain_id = :domain
                GROUP BY u.id
            SQL;

            $parameters['deliveryStatus'] = DeliveryStatus::PASS;
            $types['deliveryStatus'] = DBAL\ParameterType::STRING;

            $parameters['msgStatusAuthorized'] = MessageStatus::AUTHORIZED;
            $types['msgStatusAuthorized'] = DBAL\ParameterType::INTEGER;

            $parameters['msgStatusRestored'] = MessageStatus::RESTORED;
            $types['msgStatusRestored'] = DBAL\ParameterType::INTEGER;

            $parameters['domain'] = $domain->getId();
            $types['domain'] = DBAL\ParameterType::INTEGER;

            return $conn->executeQuery($sql, $parameters, $types)->fetchAllAssociative();
        });
    }

    /**
     * @param string[] $emails
     * @return string[]
     */
    public function searchEmails(array $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder->select('u.email');
        $queryBuilder->where("u.roles is not null");

        $expr = new Expr\Orx();
        foreach ($emails as $key => $email) {
            $expr->add("u.email LIKE :email{$key}");
            $queryBuilder->setParameter("email{$key}", "%{$email}%");
        }

        $queryBuilder->andWhere($expr);

        return $queryBuilder->getQuery()->getSingleColumnResult();
    }
}
