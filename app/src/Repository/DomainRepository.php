<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Domain>
 */
class DomainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Domain::class);
    }

    /**
     * Get the list of domains with IMAP connectors
     * @return array<int>
     */
    public function findDomainsWithIMAPConnectors(): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.connectors', 'c')
            ->where('c.type = :imap')
            ->setParameter('imap', 'IMAP')
            ->select('d.id')
            ->getQuery()
            ->getArrayResult();
    }

    public function getSearchQuery(
        User $currentUser,
        ?string $searchKey = null,
    ): Query {
        $queryBuilder = $this->createQueryBuilder('d');

        if ($searchKey) {
            $queryBuilder->where('d.domain LIKE :search')
                ->setParameter('search', '%' . $searchKey . '%');
        }

        if (!$currentUser->isSuperAdmin()) {
            $queryBuilder->andWhere(':user MEMBER OF d.users')
                ->setParameter('user', $currentUser);
        }

        $query = $queryBuilder->getQuery();

        return $query;
    }
}
