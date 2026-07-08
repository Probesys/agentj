<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\OutMsg;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template T of Message|OutMsg
 * @extends BaseRepository<T>
 */
abstract class BaseMessageRepository extends BaseRepository
{
}
