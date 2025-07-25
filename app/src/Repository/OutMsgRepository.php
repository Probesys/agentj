<?php

namespace App\Repository;

use App\Entity\OutMsg;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseMessageRepository<OutMsg>
 */
class OutMsgRepository extends BaseMessageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutMsg::class);
    }
}
