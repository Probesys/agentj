<?php

namespace App\Repository;

use App\Entity\Group;
use App\Entity\GroupsWblist;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<GroupsWblist>
 */
class GroupsWblistRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupsWblist::class);
    }

    /**
     * Get all wblist for a group
     *
     * @return array<int, int>
     */
    public function getwbListforGroup(Group $group): array
    {
        $dql = $this->createQueryBuilder('gwl')
                ->select('madr.id, gwl.wb')
                ->join('gwl.mailaddr', 'madr')
                ->where('gwl.groups = :group')
                ->setParameter('group', $group);
        $query = $dql->getQuery();
        return $query->getResult();
    }


    /**
     * @return Query<GroupsWblist>
     */
    public function getSearchQuery(Group $group, string $searchKey = ''): Query
    {
        $queryBuilder = $this->createQueryBuilder('gwl')
                ->join('gwl.mailaddr', 'madr')
                ->where('gwl.groups = :group')
                ->setParameter('group', $group);

        if ($searchKey !== '') {
            $queryBuilder->andWhere('madr.email LIKE :searchKey')
                ->setParameter('searchKey', '%' . $searchKey . '%');
        }

        return $queryBuilder->getQuery();
    }
}
