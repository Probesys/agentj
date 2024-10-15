<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sql_limit_report')]
class SqlLimitReport
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 320)]
    private string $id;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $recipientCount = 1;

    #[ORM\Column(type: 'integer')]
    private int $delta;

    #[ORM\Column(type: 'boolean')]
    private bool $processed_user = false;

    #[ORM\Column(type: 'boolean')]
    private bool $processed_admin = false;

    // Getters and setters for each property

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
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
        return $this->processed_user;
    }

    public function setProcessedUser(bool $processed_user): self
    {
        $this->processed_user = $processed_user;
        return $this;
    }

    public function isProcessedAdmin(): ?bool
    {
        return $this->processed_admin;
    }

    public function setProcessedAdmin(bool $processed_admin): self
    {
        $this->processed_admin = $processed_admin;
        return $this;
    }
}