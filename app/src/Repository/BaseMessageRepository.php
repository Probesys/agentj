<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\OutMessage;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template T of Message|OutMessage
 * @extends BaseRepository<T>
 */
abstract class BaseMessageRepository extends BaseRepository
{
}
