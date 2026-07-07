<?php

namespace App\Repository;

use App\Entity\Group;
use App\Entity\GroupRule;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<GroupRule>
 */
class GroupRuleRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupRule::class);
    }

    /**
     * Get all GroupRule for a Group
     *
     * @return array<int, int>
     */
    public function getGroupRulesForGroup(Group $group): array
    {
        $dql = $this->createQueryBuilder('gr')
            ->select('ra.id, gr.wb')
            ->join('gr.ruleAddress', 'ra')
            ->where('gr.group = :group')
            ->setParameter('group', $group);
        return $dql->getQuery()->getResult();
    }


    /**
     * @return Query<GroupRule>
     */
    public function getSearchQuery(Group $group, string $searchKey = ''): Query
    {
        $queryBuilder = $this->createQueryBuilder('gr')
            ->join('gr.ruleAddress', 'ra')
            ->where('gr.group = :group')
            ->setParameter('group', $group);

        if ($searchKey !== '') {
            $queryBuilder->andWhere('ra.email LIKE :searchKey')
                ->setParameter('searchKey', '%' . $searchKey . '%');
        }

        return $queryBuilder->getQuery();
    }
}
