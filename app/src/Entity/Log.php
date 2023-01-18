<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\EntityBlameableTrait;
use App\Entity\Traits\EntityTimestampableTrait;

#[ORM\Entity(repositoryClass: 'App\Repository\LogRepository')]
class Log
{
    use EntityBlameableTrait;
    use EntityTimestampableTrait;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'action', type: 'string', length: 255, nullable: true)]
    private $action;

    /**
     * @var string
     */
    #[ORM\Column(name: 'mailId', type: 'string', length: 255, nullable: true)]
    private $mailId;

    #[ORM\Column(type: 'text', nullable: true)]
    private $details;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getMailId(): ?string
    {
        return $this->mailId;
    }

    public function setMailId(string $mailId): self
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
