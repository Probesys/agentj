<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Schema\DefaultExpression\CurrentTimestamp;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Groups
 */
#[ORM\Table(name: 'groups')]
#[ORM\Entity(repositoryClass: 'App\Repository\GroupRepository')]
class Group
{
    use WbRuleTrait;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: 'datemod', type: 'datetime', nullable: true, options: ['default' => new CurrentTimestamp()])]
    private ?\DateTimeInterface $datemod = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Policy')]
    #[ORM\JoinColumn(name: 'policy_id', nullable: true)]
    private ?Policy $policy = null;

    /**
     * @var Collection<int, Right>
     */
    #[ORM\ManyToMany(targetEntity: Right::class, mappedBy: 'groups')]
    private Collection $rights;

    #[ORM\ManyToOne(targetEntity: Domain::class, inversedBy: 'groups')]
    #[ORM\JoinColumn(nullable: false)]
    private Domain $domain;

    #[ORM\Column(type: 'string', length: 10)]
    private string $wb;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'groups', cascade: ['persist'])]
    private Collection $users;

    /**
     * @var Collection<int, GroupRule>
     */
    #[ORM\OneToMany(targetEntity: GroupRule::class, mappedBy: 'group')]
    private Collection $groupRules;

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

    /**
     * @var Collection<int, Connector>
     */
    #[ORM\ManyToMany(targetEntity: Connector::class, mappedBy: 'targetGroups')]
    private Collection $connectors;

    public function __toString()
    {
        return $this->name;
    }


    public function __construct()
    {
        $this->datemod = new \DateTime();
        $this->rights = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->groupRules = new ArrayCollection();
        $this->connectors = new ArrayCollection();
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
     * @return Collection<int, Right>
     */
    public function getRights(): Collection
    {
        return $this->rights;
    }


    public function addRight(Right $right): self
    {
        if (!$this->rights->contains($right)) {
            $this->rights[] = $right;
            $right->addGroup($this);
        }

        return $this;
    }

    public function removeRight(Right $right): self
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
     * @return Collection<int, GroupRule>
     */
    public function getGroupRules(): Collection
    {
        return $this->groupRules;
    }

    public function addGroupRule(GroupRule $groupRule): self
    {
        if (!$this->groupRules->contains($groupRule)) {
            $this->groupRules[] = $groupRule;
            $groupRule->setGroup($this);
        }

        return $this;
    }

    public function removeGroupRule(GroupRule $groupRule): self
    {
        if ($this->groupRules->removeElement($groupRule)) {
            // set the owning side to null (unless already changed)
            if ($groupRule->getGroup() === $this) {
                $groupRule->setGroup(null);
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
     * @return array<int, array<string, int>>|null
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

    /**
     * @return Collection<int, Connector>
     */
    public function getConnectors(): Collection
    {
        return $this->connectors;
    }

    public function addConnector(Connector $connector): static
    {
        if (!$this->connectors->contains($connector)) {
            $this->connectors->add($connector);
            $connector->addTargetGroup($this);
        }

        return $this;
    }

    public function removeConnector(Connector $connector): static
    {
        if ($this->connectors->removeElement($connector)) {
            $connector->removeTargetGroup($this);
        }

        return $this;
    }
}
