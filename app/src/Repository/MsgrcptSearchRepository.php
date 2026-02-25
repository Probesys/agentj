<?php

namespace App\Repository;

use App\Amavis\ContentType;
use App\Entity\Domain;
use App\Entity\Maddr;
use App\Entity\Msgrcpt;
use App\Entity\User;
use App\Amavis\MessageStatus;
use App\Amavis\DeliveryStatus;
use App\Doctrine\SqlIndexWalker;
use App\Repository\BaseMessageRecipientRepository;
use App\Repository\UserRepository;
use App\Util\Email;
use App\Util\Search;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseMessageRecipientRepository<Msgrcpt>
 */
class MsgrcptSearchRepository extends BaseMessageRecipientRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private UserRepository $userRepository,
        #[Autowire(env: 'bool:FEATURE_FLAG_HINT_INDEX')]
        private bool $featureFlagHintIndex,
    ) {
        parent::__construct($registry, Msgrcpt::class);
    }

    /**
     * @param ?array{sort: string, direction: string} $sortParams
     */
    public function getSearchQuery(
        ?User $user,
        ?int $messageStatus = null,
        ?string $searchKey = null,
        ?array $sortParams = null,
        ?int $fromDate = null
    ): Query {


        $queryBuilder = $this->getSearchQueryBuilder($user, $messageStatus);

        if ($searchKey) {
            $this->addSearchKeyCondition($queryBuilder, $searchKey);
        }

        if (!is_null($fromDate)) {
            $queryBuilder->andWhere('m.timeNum > :date');
            $queryBuilder->setParameter('date', $fromDate);
        }

        if ($sortParams) {
            $queryBuilder->orderBy($sortParams['sort'], $sortParams['direction']);
        }

        $query = $queryBuilder->getQuery();

        $this->hintQueryWithIndexes($query, $user, $sortParams);

        $countQueryBuilder = $this->createCountQueryBuilder($queryBuilder, $user, $searchKey, $fromDate);
        $countQuery = $countQueryBuilder->getQuery();
        $count = $countQuery->getSingleScalarResult();

        $query->setHint('knp_paginator.count', $count);

        return $query;
    }


    public function countByType(?User $user = null, ?int $messageStatus = null): int
    {
        $query = $this->getSearchQuery($user, $messageStatus);
        $count = $query->getHint('knp_paginator.count');

        return $count;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByTypeAndDays(
        ?Domain $domain = null,
        ?int $fromDate = null,
        ?int $messageStatus = null,
        ?User $user = null
    ): array {
        $queryBuilder = $this->getSearchQueryBuilder($user, $messageStatus);
        $queryBuilder->select('COUNT(mr.mailId) as nb_result, m.timeIso, DATE(FROM_UNIXTIME(m.timeNum)) as date_group');

        if (!is_null($fromDate)) {
            $queryBuilder->andWhere('m.timeNum >= :date');
            $queryBuilder->setParameter('date', $fromDate);
        }

        if ($domain) {
            $queryBuilder->andWhere('d.id = :domain');
            $queryBuilder->setParameter('domain', $domain->getId());
        }

        $queryBuilder->groupBy('date_group');

        $query = $queryBuilder->getQuery();

        $query->enableResultCache(3600);

        return $query->getScalarResult();
    }

    protected function getBaseQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('mr');
        $queryBuilder->select('mr')
            ->innerJoin('App\Entity\Msgs', 'm', Join::WITH, 'm.mailId = mr.mailId AND m.partitionTag = mr.partitionTag')
            ->innerJoin('App\Entity\Maddr', 'maddr', Join::WITH, 'maddr.id = mr.rid');


        return $queryBuilder;
    }

    private function createCountQueryBuilder(
        QueryBuilder $baseQueryBuilder,
        ?User $user,
        ?string $searchKey,
        ?int $fromDate
    ): QueryBuilder {
        $countQueryBuilder = clone $baseQueryBuilder;

        $hasFilters = !empty($searchKey) || $fromDate !== null;

        if ($hasFilters) {
            $countQueryBuilder->resetDQLPart('orderBy');
            $countQueryBuilder->select('COUNT(mr.mailId)');

            return $countQueryBuilder;
        }

        $countQueryBuilder->resetDQLPart('join');
        $countQueryBuilder->resetDQLPart('from');
        $countQueryBuilder->resetDQLPart('orderBy');

        $countQueryBuilder
            ->from(Msgrcpt::class, 'mr')
            ->join(Maddr::class, 'maddr', Join::WITH, 'maddr.id = mr.rid');

        $this->addUserSpecificJoins($countQueryBuilder, $user);

        $countQueryBuilder->select('COUNT(mr.mailId)');

        return $countQueryBuilder;
    }

    private function getSearchQueryBuilder(
        ?User $user = null,
        ?int $messageStatus = null
    ): QueryBuilder {
        $queryBuilder = $this->getBaseQueryBuilder();

        $this->addUserSpecificJoins($queryBuilder, $user);

        if ($user?->isAdmin()) {
            $this->addDomainCondition($queryBuilder, $user);
        } elseif ($user) {
            $this->addRecipientsCondition($queryBuilder, $user);
        }

        if ($messageStatus === MessageStatus::UNTREATED) {
            $this->addSpamCondition($queryBuilder, $user, isSpam: false);
        } elseif ($messageStatus === MessageStatus::SPAMMED) {
            $this->addSpamCondition($queryBuilder, $user, isSpam: true);
        }

        $this->addStatusCondition($queryBuilder, $messageStatus);

        return $queryBuilder;
    }

    private function addUserSpecificJoins(QueryBuilder $queryBuilder, ?User $user): void
    {
        if (!$user || $user->isAdmin()) {
            $queryBuilder->innerJoin('App\Entity\User', 'u', Join::WITH, 'u.email = maddr.email')
               ->innerJoin('App\Entity\Domain', 'd', Join::WITH, 'd.id = u.domain');
        }
    }

    private function addStatusCondition(QueryBuilder $queryBuilder, ?int $messageStatus): void
    {
        if ($messageStatus === MessageStatus::UNTREATED) {
            $queryBuilder
                ->andWhere(<<<SQL
                    mr.ds != :ds
                    AND mr.bl != 'Y'
                    AND mr.content NOT IN (:content)
                    AND (
                        COALESCE(mr.status,0) = 0
                        OR mr.status = :messagestatusError
                    )

                SQL)
                ->setParameter('messagestatusError', MessageStatus::ERROR)
                ->setParameter('content', [ContentType::VIRUS, ContentType::CLEAN])
                ->setParameter('ds', DeliveryStatus::PASS);
        }

        if ($messageStatus === MessageStatus::DELETED) {
            $queryBuilder->andWhere('mr.status = :messageStatus and mr.content != :content')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('content', ContentType::VIRUS);
        }

        if ($messageStatus === MessageStatus::RESTORED) {
            $queryBuilder->andWhere('mr.status = :messageStatus and mr.content != :content')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('content', ContentType::VIRUS);
        }

        if ($messageStatus === MessageStatus::AUTHORIZED) {
            $queryBuilder
                ->andWhere(<<<SQL
                    mr.status = :messageStatus
                    or (
                        mr.ds = :ds
                        and (mr.status is null or mr.status = :messageStatus)
                    )
                SQL)
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('ds', DeliveryStatus::PASS);
        }

        if ($messageStatus === MessageStatus::BANNED) {
            $queryBuilder->andWhere('mr.bl = :bl or mr.status = :messageStatus')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('bl', 'Y');
        }

        if ($messageStatus === MessageStatus::SPAMMED) {
            $queryBuilder->andWhere('COALESCE(mr.status,0) = 0 and mr.content not in (:content)')
                ->setParameter('content', [ContentType::VIRUS, ContentType::CLEAN]);
        }

        if ($messageStatus === MessageStatus::VIRUS) {
            $queryBuilder->andWhere('mr.content = :content')
                ->setParameter('content', ContentType::VIRUS);
        }

        if ($messageStatus === MessageStatus::ERROR) {
            $queryBuilder->andWhere('mr.status = :messageStatus and mr.content not in (:content)')
                ->setParameter('messageStatus', $messageStatus)
                ->setParameter('content', [ContentType::VIRUS, ContentType::CLEAN]);
        }
    }

    private function addSpamCondition(QueryBuilder $queryBuilder, ?User $user, bool $isSpam): void
    {
        $comparisonOperator = $isSpam ? '>' : '<=';

        if (!$user || $user->isAdmin()) {
            $queryBuilder->andWhere('mr.bspamLevel ' . $comparisonOperator . ' d.level');
        } else {
            $level = $user->getDomain()->getLevel();
            $queryBuilder->andWhere('mr.bspamLevel ' . $comparisonOperator . ' :level')
                ->setParameter('level', $level);
        }
    }

    private function addRecipientsCondition(QueryBuilder $queryBuilder, User $user): void
    {
        $aliases = $user->getAliases()->toArray();
        $recipients = array_map(function (User $alias) {
            return $alias->getEmail();
        }, $aliases);
        $recipients[] = $user->getEmail();
        $queryBuilder->andWhere('CONVERT(maddr.email USING \'utf8\') IN (:recipients)')
            ->setParameter('recipients', $recipients);
    }

    private function addSearchKeyCondition(QueryBuilder $queryBuilder, string $searchKey): void
    {
        $potentialEmails = Email::extractEmailsFromText($searchKey, looseMode: true);
        $foundUserEmails = $this->userRepository->searchEmails($potentialEmails);
        $allUserEmails = array_unique($foundUserEmails);

        // Get the list of "potential emails" that matched a real email in the
        // database to exclude them from the boolean search below.
        $termsToExclude = [];
        foreach ($potentialEmails as $potentialEmail) {
            foreach ($allUserEmails as $email) {
                if (str_contains($email, $potentialEmail)) {
                    $termsToExclude[] = $potentialEmail;
                }
            }
        }

        // Create the boolean search string by excluding the terms that matched
        // a user's email. The emails will be used to search against the
        // recipient or the sender emails, while the other terms will be used
        // to perform a boolean search against subject, from and the message's id.
        $booleanSearch = Search::textToMariadbBooleanSearch($searchKey, excludeTerms: $termsToExclude);

        // Add the boolean search condition.
        if ($booleanSearch) {
            $queryBuilder->andWhere('MATCH(m.subject, m.fromAddr, m.messageId) AGAINST(:searchKey BOOLEAN) > 0');
            $queryBuilder->setParameter('searchKey', $booleanSearch);
        }

        // And add the recipient/sender search condition.
        if (count($allUserEmails) > 0) {
            $queryBuilder->innerJoin('App\Entity\Maddr', 'maddrSender', Join::WITH, 'maddrSender.id = m.sid');

            $queryBuilder->andWhere('(maddr.email IN (:users) OR maddrSender.email IN (:users))');
            $queryBuilder->setParameter('users', $allUserEmails);
        }
    }

    private function addDomainCondition(QueryBuilder $queryBuilder, User $user): void
    {
        if (count($user->getDomains()) > 0) {
            $queryBuilder->andWhere('u.domain in (:domain)')
                ->setParameter('domain', $user->getDomains());
        }
    }

    /**
     * @param ?array{sort: string, direction: string} $sortParams
     */
    private function hintQueryWithIndexes(Query $query, ?User $user, ?array $sortParams): void
    {
        if (!$this->featureFlagHintIndex) {
            // The "hint index" feature flag isn't enabled, so don't try to
            // optimize the requests.
            return;
        }

        $hint_index = [];

        if ($user && !$user->isAdmin()) {
            // If the request is made by a normal user, there should be not a
            // lot of emails to list. We want to priviledge the use of
            // idx_maddr_email index when joining the maddr column.
            $hint_index['maddr'] = 'USE INDEX (idx_maddr_email)';
        } elseif ($sortParams) {
            // If results are sorted, the database should use indexes
            // accordingly as it will help listing the results more quickly.
            // There is no hint for the subject sort as there is no index on
            // it. We recommand to disable sorting on subject instead (with the
            // corresponding feature flag) as it doesn't seem really useful.
            if ($sortParams['sort'] === 'maddr.email') {
                $hint_index['maddr'] = 'USE INDEX (idx_maddr_email)';
            } elseif ($sortParams['sort'] === 'm.fromAddr') {
                $hint_index['m'] = 'USE INDEX (msgs_idx_from_addr)';
            } elseif ($sortParams['sort'] === 'm.timeNum') {
                $hint_index['m'] = 'USE INDEX (msgs_idx_time_num)';
            }
        }

        // TODO hint index if searchKey or fromDate are set?

        if ($hint_index) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlIndexWalker::class);
            $query->setHint(SqlIndexWalker::HINT_INDEX, $hint_index);
        }
    }
}
