<?php

namespace App\Security;

use App\Entity\Domain;

/**
 * Implemented by entities that belong to a single Domain, for use as a
 * subject of the 'DOMAIN_ACCESS' attribute (see DomainOwnershipVoter).
 */
interface HasDomainInterface
{
    public function getDomain(): ?Domain;
}
