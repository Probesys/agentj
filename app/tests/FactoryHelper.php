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
}
