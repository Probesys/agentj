<?php

namespace App\Service;

use App\Entity\Group;
use App\Repository\SenderRuleRepository;

class GroupService
{
    public function __construct(private SenderRuleRepository $senderRuleRepository)
    {
    }

    /**
     * Regenerate the sender rules (wblist) of the given groups from their
     * group rules (groups_wblist).
     *
     * If $groups is null, the sender rules of all the groups are regenerated.
     *
     * @param ?Group[] $groups
     */
    public function updateSenderRules(?array $groups = null): void
    {
        $groupIds = null;

        if ($groups !== null) {
            $groupIds = array_values(array_unique(array_filter(array_map(
                fn (Group $group): ?int => $group->getId(),
                $groups,
            ))));

            if (count($groupIds) === 0) {
                return;
            }
        }

        $this->senderRuleRepository->deleteFromGroup($groupIds);
        $this->senderRuleRepository->insertFromGroup($groupIds);
    }
}
