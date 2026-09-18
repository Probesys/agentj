<?php

namespace App\Repository;

use App\Entity\RuleAddress;
use App\Service\RuleAddressService;
use App\Util\Email;
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

    public function findOneOrCreateByEmail(string $email, bool $flush = true): RuleAddress
    {
        $normalizedEmail = Email::normalize($email);
        $ruleAddress = $this->findOneBy(['email' => $normalizedEmail]);

        if (!$ruleAddress) {
            $ruleAddress = new RuleAddress();
            $ruleAddress->setEmail($normalizedEmail);
            $ruleAddress->setPriority(RuleAddressService::computePriority($normalizedEmail));
            $this->save($ruleAddress, $flush);
        }

        return $ruleAddress;
    }
}
