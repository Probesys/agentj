<?php

namespace App\Message;

final class AmavisAutoRelease
{
    public int $batchSize = 500;

    public function __construct(int $batchSize = 500)
    {
        $this->batchSize = $batchSize;
    }
}
