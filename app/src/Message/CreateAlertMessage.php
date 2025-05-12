<?php
// src/Message/CreateAlertMessage.php

namespace App\Message;

class CreateAlertMessage
{
    private string $type;
    private string $id;
    private string $target;

    public function __construct(string $type, string $id, string $target)
    {
        $this->type = $type;
        $this->id = $id;
        $this->target = $target;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
