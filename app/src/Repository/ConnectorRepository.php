<?php

namespace App\Repository;

use App\Entity\Connector;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Connector>
 */
class ConnectorRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Connector::class);
    }

    /**
     * @return Connector[]
     */
    public function getActiveConnectors(): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(<<<SQL
            SELECT c
            FROM App\Entity\Connector c
            JOIN c.domain d
            WHERE d.active = true

        SQL);

        return $query->getResult();
    }
}
