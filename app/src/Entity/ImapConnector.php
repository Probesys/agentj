<?php

namespace App\Entity;

use App\Repository\ImapConnectorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImapConnectorRepository::class)]
class ImapConnector extends Connector
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $imapHost = null;

    #[ORM\Column]
    private ?int $imapPort = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $imapProtocol = null;

    #[ORM\Column]
    private ?bool $imapNoValidateCert = null;

    public function getImapHost(): ?string
    {
        return $this->imapHost;
    }

    public function setImapHost(string $imapHost): static
    {
        $this->imapHost = $imapHost;

        return $this;
    }

    public function getImapPort(): ?int
    {
        return $this->imapPort;
    }

    public function setImapPort(int $imapPort): static
    {
        $this->imapPort = $imapPort;

        return $this;
    }

    public function getImapProtocol(): ?string
    {
        return $this->imapProtocol;
    }

    public function setImapProtocol(?string $imapProtocol): static
    {
        $this->imapProtocol = $imapProtocol;

        return $this;
    }

    public function isImapNoValidateCert(): ?bool
    {
        return $this->imapNoValidateCert;
    }

    public function setImapNoValidateCert(bool $imapNoValidateCert): static
    {
        $this->imapNoValidateCert = $imapNoValidateCert;

        return $this;
    }
}
