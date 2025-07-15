<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Groups>
 */
class GroupsRepository extends ServiceEntityRepository
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
     * Return the groups associated to one or more domains
     * @param Domain[] $domains
     * @return Groups[]
     */
    public function findByDomains(array $domains): array
    {
        $dql = $this->createQueryBuilder('g');

        if ($domains) {
            $dql->where('g.domain in (:domains)');
            $dql->setParameter('domains', $domains);
        }

        return $dql->getQuery()->getResult();
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
