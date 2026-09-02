<?php

namespace App\Security;

use App\Entity\User;

/**
 * Implemented by entities that belong to a single User, for use as a
 * subject of the 'IS_OWNER' attribute (see OwnershipVoter).
 */
interface OwnedByUserInterface
{
    public function getUser(): ?User;
}
