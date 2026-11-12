<?php

// src/Message/CreateAlertMessage.php

namespace App\Message;

class CreateAlertMessage
{
    private string $type;
    private string $id;
    private string $target;
    private ?int $count = null;

    public function __construct(string $type, string $id, string $target, ?int $count = null)
    {
        $this->type = $type;
        $this->id = $id;
        $this->target = $target;
        $this->count = $count;
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

    public function getCount(): ?int
    {
        return $this->count;
    }
}
