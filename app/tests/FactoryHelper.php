<?php

namespace App\Tests;

use App\Repository;
use Zenstruck\Foundry;

trait FactoryHelper
{
    /**
     * @template T of object
     *
     * @param T $entity
     */
    public function refresh(object $entity): void
    {
        $repositoryDecorator = Foundry\Persistence\repository($entity::class);
        /** @var Repository\BaseRepository<T> */
        $repository = $repositoryDecorator->inner();
        $repository->refresh($entity);
    }

    /**
     * @template T of object
     *
     * @param T|T[] $entities
     */
    public function save($entities, bool $flush = true): void
    {
        $firstEntity = is_array($entities) ?
            $entities[0]::class :
            $entities::class
        ;
        $repositoryDecorator = Foundry\Persistence\repository($firstEntity);
        /** @var Repository\BaseRepository<T> */
        $repository = $repositoryDecorator->inner();
        $repository->save($entities, $flush);
    }
}
