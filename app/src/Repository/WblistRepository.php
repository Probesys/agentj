<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\Maddr;
use App\Entity\Mailaddr;
use App\Entity\User;
use App\Entity\Wblist;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL;

/**
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
        // Score allows soft-wblisting, but we only want to handle
        // hard-wblisting in this method for now.
        if ($type === 'W') {
            $dql->andWhere("wb.wb = 'W' OR wb.wb = 'Y'");
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
                                    where g.active = true and gw.wb !='' and g.priority is not null";


        $stmt = $conn->prepare($sqlSelectGroupwbList);
        return $stmt->execute();
    }

    /**
     * Get wblist informations about a sender adress
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWbListInfoForSender(string $senderAdress, string $recipientAdress): array
    {
        $infos = [];

        $recipientDomain = explode('@', $recipientAdress)[1];
        $recipientExt = explode('.', $recipientDomain)[1];
        $recipientAddresses = [
            $recipientAdress,
            "@{$recipientDomain}",
            "@.{$recipientDomain}",
            "@.{$recipientExt}",
            '@.',
        ];

        $senderAddresses = [];
        if (!empty($senderAdress)) {
            $senderDomain = explode('@', $senderAdress)[1];
            $senderExt = explode('.', $senderDomain)[1];
            $senderAddresses = [
                $senderAdress,
                "@{$senderDomain}",
                "@.{$senderDomain}",
                "@.{$senderExt}",
                '@.',
            ];
        }

        $conn = $this->getEntityManager()->getConnection();

        $sqlSelectPolicy = <<<SQL
            SELECT *, users.id
            FROM users
            LEFT JOIN policy ON users.policy_id=policy.id
            WHERE users.email IN (:recipientAddresses)
            ORDER BY users.priority DESC
        SQL;

        $result = $conn->executeQuery($sqlSelectPolicy, [
            'recipientAddresses' => $recipientAddresses,
        ], [
            'recipientAddresses' => DBAL\ArrayParameterType::STRING,
        ])->fetchAllAssociative();

        foreach ($result as $row) {
            $id = $row['id'];
            $sqlSelectWhiteBlackList = <<<SQL
                SELECT wb, wblist.priority, wblist.datemod, wblist.group_id, wblist.sid, wblist.rid
                FROM wblist
                JOIN mailaddr ON wblist.sid = mailaddr.id
                WHERE wblist.rid = :rid
                AND mailaddr.email IN (:senderAddresses)
                ORDER BY wblist.priority DESC, mailaddr.priority DESC
            SQL;

            $result1 = $conn->executeQuery($sqlSelectWhiteBlackList, [
                'rid' => $id,
                'senderAddresses' => $senderAddresses,
            ], [
                'rid' => DBAL\ParameterType::INTEGER,
                'senderAddresses' => DBAL\ArrayParameterType::STRING,
            ])->fetchAllAssociative();

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
    public function getDefaultDomainWBList(Domain $domain): ?string
    {
        $sid = $this->getEntityManager()->getRepository(Mailaddr::class)->findOneBy(['email' => '@.']);
        $rid = $this->getEntityManager()->getRepository(User::class)->findOneBy([
            'email' => '@' . $domain->getDomain(),
        ]);
        $wb = $this->findOneBy(['rid' => $rid, 'sid' => $sid]);
        return $wb ? $wb->getWb() : null;
    }

    public function isRecipientInSenderList(Maddr $sender, Maddr $recipient): bool
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(<<<SQL
            SELECT wbl
            FROM App\Entity\Wblist wbl
            JOIN wbl.sid s
            JOIN wbl.rid r
            WHERE (wbl.wb = 'B' OR wbl.wb = 'W')
            AND s.email = :sender_email
            AND (
                r.email = :recipient_email
                OR r.email = :recipient_domain
            )
        SQL);

        $recipientEmail = $recipient->getEmailClear();
        $recipientDomain = $recipient->getReverseDomain();

        $query->setParameter('sender_email', $sender->getEmailClear());
        $query->setParameter('recipient_email', $recipientEmail);
        $query->setParameter('recipient_domain', "@{$recipientDomain}");
        $query->setMaxResults(1);

        return $query->getOneOrNullResult() !== null;
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
