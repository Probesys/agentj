<?php

namespace App\Repository;

use App\Entity\Msgs;
use App\Entity\OutMsg;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template T of Msgs|OutMsg
 * @extends BaseRepository<T>
 */
abstract class BaseMessageRepository extends BaseRepository
{
}
