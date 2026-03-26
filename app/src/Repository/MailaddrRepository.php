<?php

namespace App\Repository;

use App\Entity\Mailaddr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Mailaddr>
 */
class MailaddrRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mailaddr::class);
    }

    public function findOneOrCreateByEmail(string $email, bool $flush = true): Mailaddr
    {
        $mailaddr = $this->findOneBy(['email' => $email]);

        if (!$mailaddr) {
            $mailaddr = new Mailaddr();
            $mailaddr->setEmail($email);
            $mailaddr->setPriority(6);
            $this->save($mailaddr, $flush);
        }

        return $mailaddr;
    }
}
