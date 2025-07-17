<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sql_limit_report')]
class SqlLimitReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 320)]
    private string $mailId;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $recipientCount = 1;

    #[ORM\Column(type: 'integer')]
    private int $delta;

    #[ORM\Column(type: 'boolean')]
    private bool $processedUser = false;

    #[ORM\Column(type: 'boolean')]
    private bool $processedAdmin = false;

    // Getters and setters for each property

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getRecipientCount(): ?int
    {
        return $this->recipientCount;
    }

    public function setRecipientCount(int $recipientCount): self
    {
        $this->recipientCount = $recipientCount;
        return $this;
    }

    public function getDelta(): ?int
    {
        return $this->delta;
    }

    public function setDelta(int $delta): self
    {
        $this->delta = $delta;
        return $this;
    }

    public function isProcessedUser(): ?bool
    {
        return $this->processedUser;
    }

    public function setProcessedUser(bool $processedUser): self
    {
        $this->processedUser = $processedUser;
        return $this;
    }

    public function isProcessedAdmin(): ?bool
    {
        return $this->processedAdmin;
    }

    public function setProcessedAdmin(bool $processedAdmin): self
    {
        $this->processedAdmin = $processedAdmin;
        return $this;
    }
}
