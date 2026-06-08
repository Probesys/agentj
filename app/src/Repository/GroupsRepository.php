<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Groups>
 */
class GroupsRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Groups::class);
    }

    public function findOneByUid(string $uid): ?Groups
    {
        return $this->createQueryBuilder('g')
                        ->where('g.uid = :uid')
                        ->setParameter('uid', $uid)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    public function findOneByLdapDN(string $dn): ?Groups
    {
        return $this->createQueryBuilder('g')
                        ->where('g.ldapDN = :ldapDN')
                        ->setParameter('ldapDN', $dn)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * @param Domain[] $domains
     * @return Query<Groups>
     */
    public function getSearchQuery(array $domains, string $searchKey = ''): Query
    {
        $queryBuilder = $this->createQueryBuilder('g')
                ->join('g.domain', 'd');

        if ($domains) {
            $queryBuilder->where('g.domain in (:domains)');
            $queryBuilder->setParameter('domains', $domains);
        }

        if ($searchKey !== '') {
            $queryBuilder->andWhere('g.name LIKE :search OR d.domain LIKE :search')
                ->setParameter('search', '%' . $searchKey . '%');
        }

        return $queryBuilder->getQuery();
    }

    /**
     * Return the main (hightest priority) group of the user $user
     */
    public function getMainUserGroup(User $user): ?Groups
    {
        $dql = $this->createQueryBuilder('g')
                ->innerJoin('g.users', 'u')
                ->where('g.active = true')
                ->andWhere('u.id = :user')
                ->orderBy('g.priority', 'DESC')
                ->setParameter('user', $user);
        $query = $dql->getQuery()->setMaxResults(1);
        return $query->getOneOrNullResult();
    }

    public function getMaxPriorityforDomain(Domain $domain): int
    {
        $dql = $this->createQueryBuilder('g')
                ->select('MAX(g.priority) as max')
                ->where('g.domain = :domain')
                ->setParameter('domain', $domain);
        $query = $dql->getQuery();
        $result = $query->getOneOrNullResult();
        return $result && $result['max'] ? $result['max'] : 0;
    }

}
