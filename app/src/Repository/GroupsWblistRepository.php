<?php

namespace App\Repository;

use App\Entity\Groups;
use App\Entity\GroupsWblist;
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
    public function getwbListforGroup(Groups $group): array
    {
        $dql = $this->createQueryBuilder('gwl')
                ->select('madr.id, gwl.wb')
                ->join('gwl.mailaddr', 'madr')
                ->where('gwl.groups = :group')
                ->setParameter('group', $group);
        $query = $dql->getQuery();
        return $query->getResult();
    }
}
