<?php

namespace App\Repository;

use App\Entity\Domain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
     * Get the list of domains owned by a local administrator
     * @return Domain[]
     */
    public function getListByUserId(?int $userID = null): ?array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "select domain_id from users_domains";
        if ($userID) {
            $sql .= " where user_id=" . $userID;
        }

        $stmt = $conn->prepare($sql);

        $resultArray = $stmt->executeQuery()->fetchFirstColumn();

        if ($resultArray) {
            $dql = $this->createQueryBuilder('d')
              ->where('d in(' . implode(',', $resultArray) . ')');
            $query = $dql->getQuery();

            return $query->getResult();
        } else {
            return null;
        }
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
}
