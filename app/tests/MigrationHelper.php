<?php

namespace App\Tests;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Version\Version;

trait MigrationHelper
{
    /**
     * Execute the "up" queries of the given migration (e.g. "Version20261019210121").
     */
    protected static function runMigration(string $version): void
    {
        /** @var DependencyFactory $dependencyFactory */
        $dependencyFactory = static::getContainer()->get('doctrine.migrations.dependency_factory');
        $migration = $dependencyFactory->getMigrationRepository()
            ->getMigration(new Version("DoctrineMigrations\\{$version}"))
            ->getMigration();

        // The queries are executed by hand because Doctrine Migrations would
        // commit the test transaction when writing in its metadata table.
        $migration->up(new Schema());
        $connection = $dependencyFactory->getConnection();
        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }

        // The migration changes the database behind Doctrine's back.
        $dependencyFactory->getEntityManager()->clear();
    }
}
