<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\Maddr;
use App\Entity\Mailaddr;
use App\Entity\User;
use App\Entity\Wblist;
use App\Util\Email;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL;

/**
 * @phpstan-import-type WbRule from \App\Entity\WbRuleTrait
 *
 * @extends BaseRepository<Wblist>
 */
class WblistRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wblist::class);
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
                    'u.id as rid, ' .
                    's.id as sid, ' .
                    'wb.type as type, ' .
                    'wb.priority as priority, ' .
                    'wb.datemod, ' .
                    'u.fullname, ' .
                    's.email as email, ' .
                    'u.email as emailuser, ' .
                    'g.name as group'
                )
                ->innerJoin('wb.rid', 'u')
                ->innerJoin('wb.sid', 's')
                ->leftJoin('wb.groups', 'g');

        if (in_array('ROLE_USER', $user->getRoles())) {
            $dql->andWhere('wb.rid = :user');
            $dql->setParameter('user', $user);
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $dql->andWhere('u.domain in (:domains)');
            $dql->setParameter('domains', $user->getDomains());
        }

        // The wblist.wb attribute can either be "W or Y / B or N / space / score".
        // Score can be positive (i.e. lean towards blacklisting) or negative
        // (i.e. lean towards whitelisting). Space is neutral.
        // In AgentJ, we use the space to represent an "accepted" sender, in
        // contrast to B for "blocked" senders, and W for "allowed" senders.
        // We don't use score (except "0" at the domain level, but we don't
        // care here). See WbRuleTrait for more details.
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

    public function findOneByRecipientDomain(Domain $domain): ?Wblist
    {
        $queryBuilder = $this->createQueryBuilder('wb');
        $queryBuilder->innerJoin('wb.rid', 'r');
        $queryBuilder->innerJoin('wb.sid', 's');
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

        return $stmt->execute();
    }

    public function delete(int $rid, int $sid, int $priority): mixed
    {
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

    public function insertFromGroup(): DBAL\Result
    {
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
                                    where g.active = true and gw.wb != BINARY '' and g.priority is not null";


        $stmt = $conn->prepare($sqlSelectGroupwbList);
        return $stmt->execute();
    }

    /**
     * Get the list of Wblists that apply to the two given addresses.
     *
     * The list is ordered by priority, meaning that its first element is the
     * one which applies to the addresses.
     *
     * @return Wblist[]
     */
    public function findByMailAddresses(Maddr $sender, Maddr $recipient): array
    {
        $recipientAddresses = Email::getAddressLookups($recipient->getEmailClear());
        $senderAddresses = Email::getAddressLookups($sender->getEmailClear());

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

        $wblists = [];

        foreach ($recipientUserIds as $recipientUserId) {
            $query = $entityManager->createQuery(<<<SQL
                SELECT wb
                FROM App\Entity\Wblist wb
                JOIN wb.sid as s
                WHERE wb.rid = :recipientId
                AND s.email IN (:senderAddresses)
                ORDER BY wb.priority DESC, s.priority DESC
            SQL);

            $query->setParameter('recipientId', $recipientUserId);
            $query->setParameter('senderAddresses', $senderAddresses);
            $query->setMaxResults(1);

            $wblist = $query->getOneOrNullResult();

            if ($wblist) {
                $wblists[] = $wblist;
            }
        }

        return $wblists;
    }

    public function isSenderAuthorizedByRecipient(Maddr $sender, Maddr $recipient): bool
    {
        $wblists = $this->findByMailAddresses($sender, $recipient);

        if (count($wblists) === 0) {
            return false;
        }

        return $wblists[0]->isWbRuleAuthorized();
    }

    public function isSenderInRecipientList(Maddr $sender, Maddr $recipient): bool
    {
        $wblists = $this->findByMailAddresses($sender, $recipient);

        if (count($wblists) === 0) {
            return false;
        }

        return $wblists[0]->isWbRuleAuthorized() || $wblists[0]->isWbRuleBlocked();
    }

    /**
     * Return the default Wb for a domain
     */
    public function getDefaultDomainWBList(Domain $domain): ?Wblist
    {
        $sid = $this->getEntityManager()->getRepository(Mailaddr::class)->findOneBy(['email' => '@.']);
        $rid = $this->getEntityManager()->getRepository(User::class)->findOneBy([
            'email' => '@' . $domain->getDomain(),
        ]);
        return $this->findOneBy(['rid' => $rid, 'sid' => $sid]);
    }

    /**
     * @param WbRule $wbRule
     */
    public function updateOrCreateRule(
        User $recipientUser,
        Mailaddr $senderMailaddr,
        string $wbRule,
        int $type,
        int $priority,
        bool $flush = true,
    ): void {
        $wblist = $this->findOneBy([
            'rid' => $recipientUser,
            'sid' => $senderMailaddr,
        ]);

        if (!$wblist) {
            $wblist = new Wblist($recipientUser, $senderMailaddr);
        }

        $wblist->setWbRule($wbRule);
        $wblist->setType($type);
        $wblist->setPriority($priority);

        $this->save($wblist, $flush);
    }

    /**
     * Check if wblist is overriden by anotherOne with highter priority
     *
     * @param array<string, mixed> $wbInfo
     */
    public function wbListIsOverriden(array $wbInfo): bool
    {
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
