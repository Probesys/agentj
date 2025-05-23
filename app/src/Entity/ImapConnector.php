<?php

namespace App\Entity;

use App\Repository\ImapConnectorRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ImapConnectorRepository::class)]
class ImapConnector extends Connector
{
    public const PORT_RANGE = [1, 65535];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $imapHost = null;

    #[ORM\Column]
    #[Assert\Range(
        min: self::PORT_RANGE[0],
        max: self::PORT_RANGE[1],
    )]
    private ?int $imapPort = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $imapProtocol = null;

    #[ORM\Column]
    private ?bool $imapNoValidateCert = null;

    public function __construct()
    {
        $this->imapPort = 993;
    }

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
