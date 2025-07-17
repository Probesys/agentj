<?php

namespace App\Service;

use App\Repository\WblistRepository;

class GroupService
{
    public function __construct(private WblistRepository $wblistRepository)
    {
    }

    public function updateWblist(): void
    {
        $this->wblistRepository->deleteFromGroup();
        $this->wblistRepository->insertFromGroup();
    }
}
