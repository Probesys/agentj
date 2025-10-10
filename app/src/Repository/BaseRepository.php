<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template T of object
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class BaseRepository extends ServiceEntityRepository
{
    /**
     * @param T|T[] $entities
     */
    public function save(mixed $entities, bool $flush = true): void
    {
        if (!is_array($entities)) {
            $entities = [$entities];
        }

        $entityManager = $this->getEntityManager();

        foreach ($entities as $entity) {
            $entityManager->persist($entity);
        }

        if ($flush) {
            $entityManager->flush();
        }
    }

    /**
     * @param T|T[] $entities
     */
    public function remove(mixed $entities, bool $flush = true): void
    {
        if (!is_array($entities)) {
            $entities = [$entities];
        }

        $entityManager = $this->getEntityManager();

        foreach ($entities as $entity) {
            $entityManager->remove($entity);
        }

        if ($flush) {
            $entityManager->flush();
        }
    }
}
