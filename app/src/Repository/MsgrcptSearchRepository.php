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
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @extends BaseMessageRecipientRepository<Msgrcpt>
 *
 * @phpstan-import-type SortParams from Search
 */
class MsgrcptSearchRepository extends BaseMessageRecipientRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private UserRepository $userRepository,
        #[Autowire(env: 'bool:FEATURE_FLAG_DISABLE_SORT_BY_SUBJECT')]
        private bool $featureFlagDisableSortBySubject,
        #[Autowire(env: 'bool:FEATURE_FLAG_HINT_INDEX')]
        private bool $featureFlagHintIndex,
    ) {
        parent::__construct($registry, Msgrcpt::class);
    }

    /**
     * @param ?SortParams $sortParams
     */
    public function getSearchQuery(
        ?User $user,
        ?int $messageStatus = MessageStatus::UNTREATED,
        ?string $searchKey = null,
        ?array $sortParams = null,
        ?int $fromDate = null,
        ?float $maxSpamLevel = null,
    ): Query {

        $queryBuilder = $this->getSearchQueryBuilder($user, $messageStatus);

        if ($searchKey) {
            $this->addSearchKeyCondition($queryBuilder, $searchKey);
        }

        if (!is_null($fromDate)) {
            $queryBuilder->andWhere('m.timeNum > :date');
            $queryBuilder->setParameter('date', $fromDate);
        }

        if (!is_null($maxSpamLevel)) {
            $queryBuilder->andWhere('mr.bspamLevel <= :maxSpamLevel');
            $queryBuilder->setParameter('maxSpamLevel', $maxSpamLevel);
        }

        if ($sortParams) {
            $authorizedSortFields = ['m.timeNum', 'm.fromAddr'];
            if (!$this->featureFlagDisableSortBySubject) {
                $authorizedSortFields[] = 'm.subject';
            }
            if ($user && $user->isAdmin()) {
                $authorizedSortFields[] = 'maddr.email';
            }
            $defaultSortField = 'm.timeNum';
            $sortParams = Search::sanitizeSortParams($sortParams, $authorizedSortFields, $defaultSortField);

            $queryBuilder->orderBy($sortParams['sort'], $sortParams['direction']);
        }

        $query = $queryBuilder->getQuery();

        $this->hintQueryWithIndexes($query, $user, $sortParams, $searchKey, $fromDate);

        $countQueryBuilder = $this->createCountQueryBuilder($queryBuilder, $user, $searchKey, $fromDate);
        $countQuery = $countQueryBuilder->getQuery();
        $count = $countQuery->getSingleScalarResult();

        $query->setHint('knp_paginator.count', $count);

        return $query;
    }


    public function countByType(?User $user = null, ?int $messageStatus = MessageStatus::UNTREATED): int
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
        ?int $messageStatus = MessageStatus::UNTREATED,
        ?User $user = null
    ): array {
        $queryBuilder = $this->getSearchQueryBuilder($user, $messageStatus);
        $queryBuilder->select('COUNT(mr.mailId) as nb_result, m.timeIso, DATE(FROM_UNIXTIME(m.timeNum)) as date_group');

        if (!is_null($fromDate)) {
            $queryBuilder->andWhere('m.timeNum >= :date');
            $queryBuilder->setParameter('date', $fromDate);
        }

        if ($domain) {
            $queryBuilder->andWhere('u.domain = :domain');
            $queryBuilder->setParameter('domain', $domain);
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

        $countQueryBuilder->from(Msgrcpt::class, 'mr');

        if ($user && !$user->isSuperAdmin()) {
            // When counting, maddr is used only to filter messages by email
            // (for an end-user), or by the domains (for a simple admin).
            // Super-admins have access to all the domains and all the
            // messages, so we don't need to join this table.
            // It can be used for the "searchKey" filter too, but the "hasFilters"
            // condition above takes care of this case.
            $countQueryBuilder->join(Maddr::class, 'maddr', Join::WITH, 'maddr.id = mr.rid');
        }

        $this->addUserSpecificJoins($countQueryBuilder, $user);

        $countQueryBuilder->select('COUNT(mr.mailId)');

        return $countQueryBuilder;
    }

    private function getSearchQueryBuilder(
        ?User $user = null,
        ?int $messageStatus = MessageStatus::UNTREATED,
    ): QueryBuilder {
        $queryBuilder = $this->getBaseQueryBuilder();

        $this->addUserSpecificJoins($queryBuilder, $user);

        if ($user?->isAdmin()) {
            $this->addDomainCondition($queryBuilder, $user);
        } elseif ($user) {
            $this->addRecipientsCondition($queryBuilder, $user);
        }

        $queryBuilder->andWhere('mr.status = :messageStatus');
        $queryBuilder->setParameter('messageStatus', $messageStatus);

        return $queryBuilder;
    }

    private function addUserSpecificJoins(QueryBuilder $queryBuilder, ?User $user): void
    {
        if ($user && $user->isAdmin() && !$user->isSuperAdmin()) {
            // Users table is only used to filter the messages by the domains
            // of a "simple" admin. As super-admins have access to all the
            // domains, we don't need it in this case.
            $queryBuilder->innerJoin('App\Entity\User', 'u', Join::WITH, 'u.email = maddr.email');
        }
    }

    private function addRecipientsCondition(QueryBuilder $queryBuilder, User $user): void
    {
        $aliases = $user->getAliases()->toArray();
        $recipients = array_map(function (User $alias) {
            return $alias->getEmail();
        }, $aliases);
        $recipients[] = $user->getEmail();
        $queryBuilder->andWhere('maddr.email IN (:recipients)')
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
    private function hintQueryWithIndexes(
        Query $query,
        ?User $user,
        ?array $sortParams,
        ?string $searchKey,
        ?int $fromDate,
    ): void {
        if (!$this->featureFlagHintIndex) {
            // The "hint index" feature flag isn't enabled, so don't try to
            // optimize the requests.
            return;
        }

        $hintIndex = [];

        if ($user && !$user->isAdmin()) {
            // If the request is made by a normal user, there should be not a
            // lot of emails to list. We want to priviledge the use of
            // idx_maddr_email index when joining the maddr column.
            $hintIndex['maddr'] = 'USE INDEX (idx_maddr_email)';
        } elseif ($fromDate) {
            // This is a tricky one as, depending on the given time, the index
            // may be worth, or really not. Here, we consider it's often
            // worth for admins, but almost never for standard users.
            $hintIndex['m'] = 'USE INDEX (msgs_idx_time_num)';
        } elseif ($sortParams) {
            // If results are sorted, the database should use indexes
            // accordingly as it will help listing the results more quickly.
            // There is no hint for the subject sort as there is no index on
            // it. We recommand to disable sorting on subject instead (with the
            // corresponding feature flag) as it doesn't seem really useful.
            if ($sortParams['sort'] === 'maddr.email') {
                $hintIndex['maddr'] = 'USE INDEX (idx_maddr_email)';
            } elseif ($sortParams['sort'] === 'm.fromAddr') {
                $hintIndex['m'] = 'USE INDEX (msgs_idx_from_addr)';
            } elseif ($sortParams['sort'] === 'm.timeNum') {
                $hintIndex['m'] = 'USE INDEX (msgs_idx_time_num)';
            }
        }

        if ($searchKey) {
            // If search key is passed, we absolutely need to use the fulltext
            // index.
            $hintIndex['m'] = 'USE INDEX (msg_fulltext_idx)';
        }

        if ($hintIndex) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlIndexWalker::class);
            $query->setHint(SqlIndexWalker::HINT_INDEX, $hintIndex);
        }
    }
}
