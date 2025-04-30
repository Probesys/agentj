<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\EntityBlameableTrait;
use App\Entity\Traits\EntityTimestampableTrait;
use App\Repository\LogRepository;

#[ORM\Entity(repositoryClass: LogRepository::class)]
class Log
{
    use EntityBlameableTrait;
    use EntityTimestampableTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'action', type: 'string', length: 255, nullable: true)]
    private ?string $action = null;

    #[ORM\Column(name: 'mailId', type: 'string', length: 255, nullable: true)]
    private ?string $mailId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $details = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getMailId(): ?string
    {
        return $this->mailId;
    }

    public function setMailId(?string $mailId): self
    {
        $this->mailId = $mailId;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }
}
