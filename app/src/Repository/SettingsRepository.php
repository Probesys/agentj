<?php

namespace App\Repository;

use App\Entity\Settings;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Settings>
 */
class SettingsRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Settings::class);
    }
}
