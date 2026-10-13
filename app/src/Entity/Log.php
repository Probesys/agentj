<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\EntityBlameableTrait;
use App\Entity\Traits\EntityTimestampableTrait;
use App\Repository\LogRepository;
use App\Security\HasDomainInterface;

#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ORM\Index(name: 'log_idx_domain_created', columns: ['domain_id', 'created'])]
class Log implements HasDomainInterface
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

    #[ORM\ManyToOne(targetEntity: Domain::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Domain $domain = null;

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

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): self
    {
        $this->domain = $domain;

        return $this;
    }
}
