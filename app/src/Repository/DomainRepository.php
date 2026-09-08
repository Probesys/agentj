<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Domain>
 */
class DomainRepository extends BaseRepository
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
        $queryBuilder = $this->createAccessibleQueryBuilder($currentUser);

        if ($searchKey) {
            $queryBuilder->andWhere('d.domain LIKE :search')
                ->setParameter('search', '%' . $searchKey . '%');
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @return Domain[]
     */
    public function findActiveForUser(User $currentUser): array
    {
        return $this->createAccessibleQueryBuilder($currentUser)
            ->andWhere('d.active = :active')
            ->setParameter('active', true)
            ->orderBy('d.domain', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function createAccessibleQueryBuilder(User $currentUser): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('d');

        if (!$currentUser->isSuperAdmin()) {
            $queryBuilder->andWhere(':user MEMBER OF d.users')
                ->setParameter('user', $currentUser);
        }

        return $queryBuilder;
    }

    /**
     * Find a domain by its name
     */
    public function findOneByDomain(string $domainName): ?Domain
    {
        return $this->findOneBy(['domain' => strtolower($domainName)]);
    }
}
