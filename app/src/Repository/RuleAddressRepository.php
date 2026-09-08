<?php

namespace App\Repository;

use App\Entity\RuleAddress;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<RuleAddress>
 */
class RuleAddressRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RuleAddress::class);
    }

    public function findOneOrCreateByEmail(string $email, bool $flush = true, ?int $priority = null): RuleAddress
    {
        $ruleAddress = $this->findOneBy(['email' => $email]);

        if (!$ruleAddress) {
            $ruleAddress = new RuleAddress();
            $ruleAddress->setEmail($email);
            $ruleAddress->setPriority($priority ?? 6);
            $this->save($ruleAddress, $flush);
        }

        return $ruleAddress;
    }
}
