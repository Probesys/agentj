<?php

namespace App\Entity;

use App\Entity\Traits\EntityBlameableTrait;
use App\Entity\Traits\EntityTimestampableTrait;
use App\Repository\ConnectorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConnectorRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['office365' => 'Office365Connector', 'LDAP' => 'LdapConnector', 'Imap' => 'ImapConnector'])]
class Connector
{
    use EntityBlameableTrait;
    use EntityTimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Domain::class, inversedBy: 'connectors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Domain $domain;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'originConnector')]
    private Collection $users;

    /**
     * @var Collection<int, Groups>
     */
    #[ORM\OneToMany(targetEntity: Groups::class, mappedBy: 'originConnector')]
    private Collection $groups;

    #[ORM\Column(nullable: true)]
    private ?bool $synchronizeGroup = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->groups = new ArrayCollection();
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

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setOriginConnector($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getOriginConnector() === $this) {
                $user->setOriginConnector(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Groups>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Groups $group): self
    {
        if (!$this->groups->contains($group)) {
            $this->groups[] = $group;
            $group->setOriginConnector($this);
        }

        return $this;
    }

    public function removeGroup(Groups $group): self
    {
        if ($this->groups->removeElement($group)) {
            // set the owning side to null (unless already changed)
            if ($group->getOriginConnector() === $this) {
                $group->setOriginConnector(null);
            }
        }

        return $this;
    }

    public function isSynchronizeGroup(): ?bool
    {
        return $this->synchronizeGroup;
    }

    public function setSynchronizeGroup(?bool $synchronizeGroup): self
    {
        $this->synchronizeGroup = $synchronizeGroup;

        return $this;
    }
}
