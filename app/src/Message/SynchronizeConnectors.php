<?php

namespace App\Message;

final class SynchronizeConnectors
{
    /**
     * @param 'ldap'|'o365'|'all' $type
     */
    public function __construct(private string $type)
    {
    }

    public function getType(): string
    {
        return $this->type;
    }
}
