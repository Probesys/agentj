<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MessageStatusRepository;

#[ORM\Entity(repositoryClass: MessageStatusRepository::class)]
class MessageStatus
{

    const UNTREATED = null;
    const BANNED = 1;
    const AUTHORIZED = 2;
    const DELETED = 3;
    const ERROR = 4;
    const RESTORED = 5;
    const SPAMMED = 6;
    const VIRUS = 7;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    /**
     * @var Collection<int, Msgrcpt>
     */
    #[ORM\OneToMany(targetEntity: Msgrcpt::class, mappedBy: 'status')]
    private Collection $msgrcpts;

    public function __construct()
    {
        $this->msgrcpts = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Msgrcpt>
     */
    public function getMsgrcpts(): Collection
    {
        return $this->msgrcpts;
    }

    public function addMsgrcpt(Msgrcpt $msgrcpt): self
    {
        if (!$this->msgrcpts->contains($msgrcpt)) {
            $this->msgrcpts[] = $msgrcpt;
            $msgrcpt->setStatus($this);
        }

        return $this;
    }

    public function removeMsgrcpt(Msgrcpt $msgrcpt): self
    {
        if ($this->msgrcpts->contains($msgrcpt)) {
            $this->msgrcpts->removeElement($msgrcpt);
            // set the owning side to null (unless already changed)
            if ($msgrcpt->getStatus() === $this) {
                $msgrcpt->setStatus(null);
            }
        }

        return $this;
    }
}
