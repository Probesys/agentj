<?php

namespace App\Repository;

use App\Entity\Groups;
use App\Entity\GroupsWblist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Captcha|null find($id, $lockMode = null, $lockVersion = null)
 * @method Captcha|null findOneBy(array $criteria, array $orderBy = null)
 * @method Captcha[]    findAll()
 * @method Captcha[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupsWblistRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupsWblist::class);
    }

    /**
     * Get all wblist for a group
     * @param Groups $group
     * @return array
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
