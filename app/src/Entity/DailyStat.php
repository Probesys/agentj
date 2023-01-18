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
    private $id;

    #[ORM\Column(type: 'date')]
    private $date;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbUntreated;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbSpam;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbVirus;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbAuthorized;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbBanned;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbDeleted;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbRestored;

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
}
