<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;

trait LogHelper
{
    /**
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLogs(): array
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $sql = 'SELECT * FROM log';
        $result = $entityManager->getConnection()->executeQuery($sql);
        return $result->fetchAllAssociative();
    }
}
