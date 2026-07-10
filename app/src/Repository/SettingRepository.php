<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Setting>
 */
class SettingRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }
}
