<?php

namespace App\Repository;

use App\Entity\Groups;
use App\Entity\GroupsWblist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupsWblist>
 */
class GroupsWblistRepository extends ServiceEntityRepository
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
    public function getwbListforGroup(Groups $group): array {
        $dql = $this->createQueryBuilder('gwl')
                ->select('madr.id, gwl.wb')
                ->join('gwl.mailaddr', 'madr')
                ->where('gwl.groups = :group')
                ->setParameter('group', $group);
        $query = $dql->getQuery();
        return $query->getResult();
    }
}
