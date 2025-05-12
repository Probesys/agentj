<?php

namespace App\Entity;

use App\Repository\DailyStatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DailyStatRepository::class)]
class DailyStat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $date;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbUntreated = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbSpam = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbVirus = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbAuthorized = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbBanned = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbDeleted = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbRestored = null;

    #[ORM\ManyToOne(inversedBy: 'dailyStats')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Domain $domain = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNbUntreated(): ?int
    {
        return $this->nbUntreated;
    }

    public function setNbUntreated(?int $nbUntreated): self
    {
        $this->nbUntreated = $nbUntreated;

        return $this;
    }

    public function getNbSpam(): ?int
    {
        return $this->nbSpam;
    }

    public function setNbSpam(?int $nbSpam): self
    {
        $this->nbSpam = $nbSpam;

        return $this;
    }

    public function getNbVirus(): ?int
    {
        return $this->nbVirus;
    }

    public function setNbVirus(?int $nbVirus): self
    {
        $this->nbVirus = $nbVirus;

        return $this;
    }

    public function getNbAuthorized(): ?int
    {
        return $this->nbAuthorized;
    }

    public function setNbAuthorized(?int $nbAuthorized): self
    {
        $this->nbAuthorized = $nbAuthorized;

        return $this;
    }

    public function getNbBanned(): ?int
    {
        return $this->nbBanned;
    }

    public function setNbBanned(?int $nbBanned): self
    {
        $this->nbBanned = $nbBanned;

        return $this;
    }

    public function getNbDeleted(): ?int
    {
        return $this->nbDeleted;
    }

    public function setNbDeleted(?int $nbDeleted): self
    {
        $this->nbDeleted = $nbDeleted;

        return $this;
    }

    public function getNbRestored(): ?int
    {
        return $this->nbRestored;
    }

    public function setNbRestored(?int $nbRestored): self
    {
        $this->nbRestored = $nbRestored;

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
