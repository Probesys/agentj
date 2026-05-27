<?php

namespace App\Message;

final class SynchronizeConnectors
{
    /**
     * @param int|'all' $id
     */
    public function __construct(
        private int|string $id = 'all'
    ) {
    }

    /**
     * @return int|'all'
     */
    public function getId(): int|string
    {
        return $this->id;
    }
}
