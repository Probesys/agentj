<?php

namespace App\Service;

use App\Repository\SenderRuleRepository;

class GroupService
{
    public function __construct(private SenderRuleRepository $senderRuleRepository)
    {
    }

    public function updateSenderRules(): void
    {
        $this->senderRuleRepository->deleteFromGroup();
        $this->senderRuleRepository->insertFromGroup();
    }
}
