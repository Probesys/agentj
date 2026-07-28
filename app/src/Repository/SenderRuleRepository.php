<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\Domain;
use App\Entity\RuleAddress;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Util\Email;
use Doctrine\DBAL;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @phpstan-import-type WbRule from \App\Entity\RuleTrait
 *
 * @extends BaseRepository<SenderRule>
 */
class SenderRuleRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SenderRule::class);
    }

    /**
     * @param 'W'|'B'|'any' $type
     * @param ?array{
     *     field: 'emailuser'|'email'|'wb.datemod',
     *     direction: 'asc'|'desc',
     * } $sort
     * @return array<int, array<string, mixed>>
     */
    public function search(
        string $type,
        User $user,
        string $query = '',
        ?array $sort = null
    ): array {
        $dql = $this->createQueryBuilder('wb')
                ->select(
                    'u.id as userId, ' .
                    's.id as senderRuleAddressId, ' .
                    'wb.type as type, ' .
                    'wb.priority as priority, ' .
                    'wb.datemod, ' .
                    'u.fullname, ' .
                    's.email as email, ' .
                    'u.email as emailuser, ' .
                    'g.name as group'
                )
                ->innerJoin('wb.user', 'u')
                ->innerJoin('wb.senderRuleAddress', 's')
                ->leftJoin('wb.group', 'g');

        if (in_array('ROLE_USER', $user->getRoles())) {
            $dql->andWhere('wb.user = :user');
            $dql->setParameter('user', $user);
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $dql->andWhere('u.domain in (:domains)');
            $dql->setParameter('domains', $user->getDomains());
        }

        // The SenderRule.wb attribute can either be "W or Y / B or N / space / score".
        // Score can be positive (i.e. lean towards blacklisting) or negative
        // (i.e. lean towards whitelisting). Space is neutral.
        // In AgentJ, we use the space to represent an "accepted" sender, in
        // contrast to B for "blocked" senders, and W for "allowed" senders.
        // We don't use score (except "0" at the domain level, but we don't
        // care here). See RuleTrait for more details.
        if ($type === 'W') {
            $dql->andWhere("wb.wb = 'W' OR wb.wb = 'Y' OR wb.wb = ' '");
        } elseif ($type === 'B') {
            $dql->andWhere("wb.wb = 'B' OR wb.wb = 'N'");
        }

        if ($query) {
            $whereQuery = 'LOWER(s.email) LIKE LOWER(:query)';

            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $whereQuery .= ' OR LOWER(u.email) LIKE LOWER(:query)';
                $whereQuery .= ' OR LOWER(u.fullname) LIKE LOWER(:query)';
            }

            $dql->andWhere($whereQuery);
            $dql->setParameter('query', "%{$query}%");
        }

        if ($sort) {
            $dql->orderBy($sort['field'], $sort['direction']);
        }

        return $dql->getQuery()->getScalarResult();
    }

    public function findOneByRecipientDomain(Domain $domain): ?SenderRule
    {
        $queryBuilder = $this->createQueryBuilder('wb');
        $queryBuilder->innerJoin('wb.user', 'r');
        $queryBuilder->innerJoin('wb.senderRuleAddress', 's');
        $queryBuilder->where("s.email = '@.'");
        $queryBuilder->andWhere('r.email = :domain');
        $queryBuilder->setParameter('domain', "@{$domain->getDomain()}");

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function deleteFromGroup(): DBAL\Result
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = " DELETE FROM wblist "
                . " WHERE group_id  is not null";
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery();
    }

    public function delete(int $userId, int $senderRuleAddressId, int $priority): mixed
    {
        $qdl = $this->createQueryBuilder('wb')
                ->delete()
                ->where('wb.user =:userId')
                ->andWhere('wb.senderRuleAddress =:senderRuleAddressId')
                ->andWhere('wb.priority =:priority')
                ->setParameter('userId', $userId)
                ->setParameter('senderRuleAddressId', $senderRuleAddressId)
                ->setParameter('priority', $priority);

        return $qdl->getQuery()->execute();
    }

    public function insertFromGroup(): DBAL\Result
    {
        $conn = $this->getEntityManager()->getConnection();
        $sqlSelectGroupRule = "insert into wblist (rid, sid, group_id, wb, datemod, type, priority)
                                    select u.id ,gw.sid, ug.groups_id, gw.wb, NOW(),'2',
                                    CASE g.override_user WHEN 1 THEN "
                                        . SenderRule::PRIORITY_GROUP_OVERRIDE . " + g.priority" .
                                        " WHEN 0 THEN "
                                        . SenderRule::PRIORITY_GROUP . " + g.priority
                                    END as 'priority'  from users u
                                    inner join user_groups ug on ug.user_id =u.id
                                    inner join groups g on g.id =ug.groups_id
                                    inner join groups_wblist gw on gw.group_id =g.id
                                    where g.active = true and gw.wb != BINARY '' and g.priority is not null";

        return $conn->prepare($sqlSelectGroupRule)->executeQuery();
    }

    /**
     * Get the list of sender rules that apply to the two given addresses.
     *
     * The list is ordered by priority, meaning that its first element is the
     * one which applies to the addresses.
     *
     * @return SenderRule[]
     */
    public function findBySenderEmailAndRecipient(string $senderEmail, Address $recipient): array
    {
        $recipientAddresses = Email::getAddressLookups($recipient->getEmail());
        $senderAddresses = Email::getAddressLookups($senderEmail);

        $entityManager = $this->getEntityManager();
        $connection = $entityManager->getConnection();

        $sqlSelectUserIds = <<<SQL
            SELECT users.id
            FROM users
            WHERE users.email IN (:recipientAddresses)
            ORDER BY users.priority DESC
        SQL;

        $recipientUserIds = $connection->executeQuery($sqlSelectUserIds, [
            'recipientAddresses' => $recipientAddresses,
        ], [
            'recipientAddresses' => DBAL\ArrayParameterType::STRING,
        ])->fetchFirstColumn();

        $senderRules = [];

        foreach ($recipientUserIds as $recipientUserId) {
            $query = $entityManager->createQuery(<<<SQL
                SELECT sr
                FROM App\Entity\SenderRule sr
                JOIN sr.senderRuleAddress as s
                WHERE sr.user = :recipientId
                AND s.email IN (:senderAddresses)
                ORDER BY sr.priority DESC, s.priority DESC
            SQL);

            $query->setParameter('recipientId', $recipientUserId);
            $query->setParameter('senderAddresses', $senderAddresses);
            $query->setMaxResults(1);

            $senderRule = $query->getOneOrNullResult();

            if ($senderRule) {
                $senderRules[] = $senderRule;
            }
        }

        return $senderRules;
    }

    public function isSenderAuthorizedByRecipient(string $senderEmail, Address $recipient): bool
    {
        $senderRules = $this->findBySenderEmailAndRecipient($senderEmail, $recipient);

        if (count($senderRules) === 0) {
            return false;
        }

        return $senderRules[0]->isWbRuleAuthorized();
    }

    public function isSenderInRecipientList(string $senderEmail, Address $recipient): bool
    {
        $senderRules = $this->findBySenderEmailAndRecipient($senderEmail, $recipient);

        if (count($senderRules) === 0) {
            return false;
        }

        return $senderRules[0]->isWbRuleAuthorized() || $senderRules[0]->isWbRuleBlocked();
    }

    /**
     * Return the default Wb for a domain
     */
    public function getDefaultDomainSenderRule(Domain $domain): ?SenderRule
    {
        $senderRuleAddress = $this->getEntityManager()->getRepository(RuleAddress::class)->findOneBy(['email' => '@.']);
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy([
            'email' => '@' . $domain->getDomain(),
        ]);
        return $this->findOneBy(['user' => $user, 'senderRuleAddress' => $senderRuleAddress]);
    }

    /**
     * @param WbRule $wbRule
     */
    public function updateOrCreateRule(
        User $recipientUser,
        RuleAddress $senderRuleAddress,
        string $wbRule,
        int $type,
        int $priority,
        bool $flush = true,
    ): void {
        $senderRule = $this->findOneBy([
            'user' => $recipientUser,
            'senderRuleAddress' => $senderRuleAddress,
        ]);

        if (!$senderRule) {
            $senderRule = new SenderRule($recipientUser, $senderRuleAddress);
        }

        $senderRule->setWbRule($wbRule);
        $senderRule->setType($type);
        $senderRule->setPriority($priority);

        $this->save($senderRule, $flush);
    }

    /**
     * Check if sender rule is overridden by anotherOne with higher priority
     *
     * @param array<string, mixed> $wbInfo
     */
    public function senderRuleIsOverridden(array $wbInfo): bool
    {
        $dql = $this->createQueryBuilder('wb')
                ->select('wb')
                ->where('wb.user =:user')
                ->andWhere('wb.senderRuleAddress =:senderRuleAddress')
                ->andWhere('wb.priority > :priority')
                ->setParameter('user', $wbInfo['userId'])
                ->setParameter('senderRuleAddress', $wbInfo['senderRuleAddressId'])
                ->setParameter('priority', $wbInfo['priority'])
                ->setMaxResults(1);

        $query = $dql->getQuery();
        $result = $query->getOneOrNullResult();
        return !is_null($result);
    }

    /**
     * @return Query<SenderRule>
     */
    public function getDomainSearchQuery(User $domainUser, string $searchKey = ''): Query
    {
        $queryBuilder = $this->createQueryBuilder('wb')
            ->join('wb.senderRuleAddress', 'sender')
            ->where('wb.user =:user')
            ->setParameter('user', $domainUser);

        if ($searchKey !== '') {
            $queryBuilder->andWhere('sender.email LIKE :searchKey')
                ->setParameter('searchKey', '%' . $searchKey . '%');
        }

        return $queryBuilder->getQuery();
    }
}
