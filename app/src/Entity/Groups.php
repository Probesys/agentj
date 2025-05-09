<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
//use Gedmo\
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\DBAL\Types\Types;

/**
 * Groups
 */
#[ORM\Table(name: 'groups')]
#[ORM\Entity(repositoryClass: 'App\Repository\GroupsRepository')]
class Groups
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: 'datemod', type: 'datetime', nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $datemod = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Policy')]
    #[ORM\JoinColumn(name: 'policy_id', nullable: true)]
    private ?Policy $policy = null;

    /**
     * @var Collection<int, Rights>
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Rights', mappedBy: 'groups')]
    private Collection $rights;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Domain', inversedBy: 'groups')]
    #[ORM\JoinColumn(nullable: false)]
    private Domain $domain;

    #[ORM\Column(type: 'string', length: 10)]
    private string $wb;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\User', mappedBy: 'groups', cascade: ['persist'])]
    private Collection $users;

    /**
     * @var Collection<int, GroupsWblist>
     */
    #[ORM\OneToMany(targetEntity: 'App\Entity\GroupsWblist', mappedBy: 'groups')]
    private Collection $groupsWbLists;

    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(type: 'string', length: 128, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $overrideUser = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $active = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $priority = 1;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $uid = null;

    #[ORM\ManyToOne(targetEntity: Connector::class, inversedBy: 'groups')]
    private ?Connector $originConnector = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ldapDN = null;

    /**
     * @var array<int, array<string, int>>
     */
    #[ORM\Column(nullable: true)]
    private ?array $quota = null;

    public function __toString()
    {
        return $this->name;
    }


    public function __construct()
    {
        $this->datemod = new \DateTime();
        $this->rights = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->groupsWbLists = new ArrayCollection();
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



    public function getDatemod(): ?\DateTimeInterface
    {
        return $this->datemod;
    }

    public function setDatemod(\DateTimeInterface $datemod): self
    {
        $this->datemod = $datemod;

        return $this;
    }



    /**
     * @return Collection<int, Rights>
     */
    public function getRights(): Collection
    {
        return $this->rights;
    }


    public function addRight(Rights $right): self
    {
        if (!$this->rights->contains($right)) {
            $this->rights[] = $right;
            $right->addGroup($this);
        }

        return $this;
    }

    public function removeRight(Rights $right): self
    {
        if ($this->rights->contains($right)) {
            $this->rights->removeElement($right);
            $right->removeGroup($this);
        }

        return $this;
    }


    public function getPolicy(): ?Policy
    {
        return $this->policy;
    }

    public function setPolicy(?Policy $policy): self
    {
        $this->policy = $policy;

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

    public function getWb(): ?string
    {
        return $this->wb;
    }

    public function setWb(string $wb): self
    {
        $this->wb = $wb;

        return $this;
    }


    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getOverrideUser(): ?bool
    {
        return $this->overrideUser;
    }

    public function setOverrideUser(?bool $overrideUser): self
    {
        $this->overrideUser = $overrideUser;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function isOverrideUser(): ?bool
    {
        return $this->overrideUser;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @return Collection<int, GroupsWblist>
     */
    public function getGroupsWbLists(): Collection
    {
        return $this->groupsWbLists;
    }

    public function addGroupsWbList(GroupsWblist $groupsWbList): self
    {
        if (!$this->groupsWbLists->contains($groupsWbList)) {
            $this->groupsWbLists[] = $groupsWbList;
            $groupsWbList->setGroups($this);
        }

        return $this;
    }

    public function removeGroupsWbList(GroupsWblist $groupsWbList): self
    {
        if ($this->groupsWbLists->removeElement($groupsWbList)) {
            // set the owning side to null (unless already changed)
            if ($groupsWbList->getGroups() === $this) {
                $groupsWbList->setGroups(null);
            }
        }

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
            $user->addGroup($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeGroup($this);
        }

        return $this;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(?string $uid): self
    {
        $this->uid = $uid;

        return $this;
    }

    public function getOriginConnector(): ?Connector
    {
        return $this->originConnector;
    }

    public function setOriginConnector(?Connector $originConnector): self
    {
        $this->originConnector = $originConnector;

        return $this;
    }

    public function getLdapDN(): ?string
    {
        return $this->ldapDN;
    }

    public function setLdapDN(?string $ldapDN): self
    {
        $this->ldapDN = $ldapDN;

        return $this;
    }

    /**
     * @return array<int, array<string, int>>
     */
    public function getQuota(): ?array
    {
        return $this->quota;
    }

    /**
     * @param array<int, array<string, int>> $quota
     */
    public function setQuota(?array $quota): static
    {
        $this->quota = $quota;

        return $this;
    }
}
