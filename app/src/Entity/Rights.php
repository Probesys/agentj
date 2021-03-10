<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="rights")
 * @ORM\Entity(repositoryClass="App\Repository\RightsRepository")
 */
class Rights
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $system_name;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Groups", inversedBy="rights")
     * @ORM\JoinTable(name="rights_groups")
     */
    private $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }
    
    public function __toString() {
        return $this->name;
    }
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSystemName(): ?string
    {
        return $this->system_name;
    }

    public function setSystemName(string $system_name): self
    {
        $this->system_name = $system_name;

        return $this;
    }

    /**
     * @return Collection|Groups[]
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Groups $group): self
    {
        if (!$this->groups->contains($group)) {
            $this->groups[] = $group;
        }

        return $this;
    }

    public function removeGroup(Groups $group): self
    {
        if ($this->groups->contains($group)) {
            $this->groups->removeElement($group);
        }

        return $this;
    }
}
