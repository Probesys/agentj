<?php
// src/Repository/OutMsgRepository.php

namespace App\Repository;

use App\Entity\OutMsg;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OutMsg|null find($id, $lockMode = null, $lockVersion = null)
 * @method OutMsg|null findOneBy(array $criteria, array $orderBy = null)
 * @method OutMsg[]    findAll()
 * @method OutMsg[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OutMsgRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutMsg::class);
    }

    /**
     * Find unprocessed OutMsg entities with specific content.
     *
     * @param string $content
     * @return OutMsg[]
     */
    public function findUnprocessedByContent(string $content): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.content = :content')
            ->andWhere('o.processed = :processed')
            ->setParameter('content', $content)
            ->setParameter('processed', false)
            ->getQuery()
            ->getResult();
    }
}