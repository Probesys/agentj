<?php

namespace App\Repository;

use App\Entity\Alert;
use App\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Alert>
 */
class AlertRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alert::class);
    }

    /**
     * @return Query<Alert>
     */
    public function getUserSearchQuery(
        User $user,
        string $searchKey
    ): Query {

        $queryBuilder =  $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $user);

        if ($searchKey !== '') {
            $queryBuilder->andWhere('a.refUser LIKE :searchKey')
                ->setParameter('searchKey', '%' . $searchKey . '%');
        }

        return $queryBuilder->getQuery();
    }
}
