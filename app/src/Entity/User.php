<?php

namespace App\Entity;

use App\Entity\Policy;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\DBAL\Types\Types;

/**
 * Users
 */
#[ORM\Table(name: 'users')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], ignoreNull: true)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'priority', type: 'integer', nullable: false, options: ['default' => 7])]
    private int $priority = 7;

    #[ORM\Column(name: 'email', type: Types::BINARY, nullable: true, unique: true)]
    private mixed $email = null;

    #[ORM\Column(name: 'fullname', type: 'string', length: 255, nullable: true)]
    private ?string $fullname = null;

    #[ORM\Column(name: 'username', type: 'string', length: 255, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(name: 'local', type: 'string', length: 1, nullable: true, options: ['fixed' => true])]
    private ?string $local = null;

    #[ORM\Column(name: 'password', type: 'string', length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(name: 'roles', type: 'text', length: 0, nullable: true)]
    private ?string $roles = null;

    #[ORM\Column(name: 'emailRecovery', type: 'string', length: 255, nullable: true)]
    private ?string $emailRecovery = null;

    #[ORM\Column(name: 'imapLogin', type: 'string', length: 255, nullable: true)]
    private ?string $imapLogin = null;


    #[ORM\ManyToOne(targetEntity: Policy::class)]
    #[ORM\JoinColumn(name: 'policy_id', nullable: true)]
    private ?Policy $policy;

    #[ORM\ManyToOne(targetEntity: Policy::class)]
    #[ORM\JoinColumn(name: 'out_policy_id', nullable: true)]
    private ?Policy $outPolicy;

    /**
     * @var Collection<int, Domain>
     */
    #[ORM\JoinTable(name: 'users_domains')]
    #[ORM\ManyToMany(targetEntity: Domain::class, inversedBy: 'users')]
    private Collection $domains;

    #[ORM\ManyToOne(targetEntity: Domain::class)]
    #[ORM\JoinColumn(name: 'domain_id', nullable: true, onDelete: 'CASCADE')]
    private ?Domain $domain;

    /**
     * @var Collection<int, Groups>
     */
    #[ORM\ManyToMany(targetEntity: Groups::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[OrderBy(['priority' => 'DESC'])]
    private Collection $groups;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'aliases')]
    #[ORM\JoinColumn(name: 'original_user_id', nullable: true, onDelete: 'CASCADE')]
    private ?User $originalUser;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'originalUser')]
    private Collection $aliases;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $report;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'ownedSharedBoxes')]
    private Collection $sharedWith;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'sharedWith')]
    private Collection $ownedSharedBoxes;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $dateLastReport;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $bypassHumanAuth;

    #[ORM\Column(type: 'string', length: 5, nullable: true)]
    private ?string $preferedLang;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $uid;

    /**
     * @var Collection<int, Wblist>
     */
    #[ORM\OneToMany(targetEntity: Wblist::class, mappedBy: 'rid')]
    private Collection $wbLists;

    #[ORM\ManyToOne(targetEntity: Connector::class, inversedBy: 'users')]
    private ?Connector $originConnector;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ldapDN;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $office365PrincipalName = null;

    #[ORM\OneToOne(targetEntity: SenderRateLimit::class)]
    private ?SenderRateLimit $senderRateLimit = null;

    /**
     * @var array<int, array<string, int>>
     */
    #[ORM\Column(nullable: true)]
    private ?array $quota = null;

    public function __construct()
    {

        $this->domains = new ArrayCollection();
        $this->sharedWith = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->ownedSharedBoxes = new ArrayCollection();
        $this->wbLists = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getEmailFromRessource() ?: '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getEmail(): mixed
    {
        return $this->email;
    }

    public function getEmailFromRessource(): ?string
    {
        if ($this->email === null) {
            return null;
        }

        $emailClear = stream_get_contents($this->email, -1, 0);
        if ($emailClear === false) {
            return null;
        }
        return $emailClear;
    }

    public function setEmail(mixed $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(?string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getLocal(): ?string
    {
        return $this->local;
    }

    public function setLocal(?string $local): self
    {
        $this->local = $local;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = json_decode($this->roles, associative: true);

        if (!is_array($roles)) {
            $roles = [];
        }

        // Afin d'être sûr qu'un user a toujours au moins 1 rôle
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function setRoles(string $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Retour le salt qui a servi à coder le mot de passe
     *
     * {@inheritdoc}
     */
    public function getSalt(): ?string
    {
        // See "Do you need to use a Salt?" at https://symfony.com/doc/current/cookbook/security/entity_provider.html
        // we're using bcrypt in security.yml to encode the password, so
        // the salt value is built-in and you don't have to generate one

        return null;
    }

    /**
     * Removes sensitive data from the user.
     *
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
        // Nous n'avons pas besoin de cette methode car nous n'utilions pas de plainPassword
        // Mais elle est obligatoire car comprise dans l'interface UserInterface
        // $this->plainPassword = null;
    }

    public function getUsername(): ?string
    {
        if ($this->username === null) {
            return (string) $this->fullname;
        }
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

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

    public function getOutPolicy(): ?Policy
    {
        return $this->outPolicy;
    }

    public function setOutPolicy(?Policy $outPolicy): self
    {
        $this->outPolicy = $outPolicy;

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

    /**
     * @return Collection<int, Domain>
     */
    public function getDomains(): Collection
    {
        return $this->domains;
    }

    public function addDomain(Domain $domain): self
    {
        if (!$this->domains->contains($domain)) {
            $this->domains[] = $domain;
        }

        return $this;
    }

    public function removeDomain(Domain $domain): self
    {
        if ($this->domains->contains($domain)) {
            $this->domains->removeElement($domain);
        }

        return $this;
    }

    public function hasDomain(Domain $domain): bool
    {
        return $this->domains->contains($domain);
    }

    public function getEmailRecovery(): ?string
    {
        return $this->emailRecovery;
    }

    public function setEmailRecovery(?string $emailRecovery): self
    {
        $this->emailRecovery = $emailRecovery;

        return $this;
    }

    public function getOriginalUser(): ?self
    {
        return $this->originalUser;
    }

    public function setOriginalUser(?self $originalUser): self
    {
        $this->originalUser = $originalUser;

        return $this;
    }

    public function getImapLogin(): ?string
    {
        return $this->imapLogin;
    }

    public function setImapLogin(?string $imapLogin): self
    {
        $this->imapLogin = $imapLogin;

        return $this;
    }

    public function getReport(): ?bool
    {
        return $this->report;
    }

    public function setReport(?bool $report): self
    {
        $this->report = $report;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSharedWith(): Collection
    {
        return $this->sharedWith;
    }

    public function addSharedWith(self $sharedWith): self
    {
        if (!$this->sharedWith->contains($sharedWith)) {
            $this->sharedWith[] = $sharedWith;
        }

        return $this;
    }

    public function removeSharedWith(self $sharedWith): self
    {
        if ($this->sharedWith->contains($sharedWith)) {
            $this->sharedWith->removeElement($sharedWith);
        }

        return $this;
    }


    /**
     * @return Collection<int, User>
     */
    public function getOwnedSharedBoxes(): Collection
    {
        return $this->ownedSharedBoxes;
    }

    public function addOwnedSharedBox(User $ownedSharedBox): self
    {
        if (!$this->ownedSharedBoxes->contains($ownedSharedBox)) {
            $this->ownedSharedBoxes->add($ownedSharedBox);
            $ownedSharedBox->addSharedWith($this);
        }

        return $this;
    }

    public function removeOwnedSharedBox(User $ownedSharedBox): self
    {
        if ($this->ownedSharedBoxes->removeElement($ownedSharedBox)) {
            $ownedSharedBox->removeSharedWith($this);
        }

        return $this;
    }

    public function getDateLastReport(): ?int
    {
        return $this->dateLastReport;
    }

    public function setDateLastReport(?int $dateLastReport): self
    {
        $this->dateLastReport = $dateLastReport;

        return $this;
    }

    public function getBypassHumanAuth(): ?bool
    {
        return $this->bypassHumanAuth;
    }

    public function setBypassHumanAuth(?bool $bypassHumanAuth): self
    {
        $this->bypassHumanAuth = $bypassHumanAuth;

        return $this;
    }

    public function getPreferedLang(): ?string
    {
        return $this->preferedLang;
    }

    public function setPreferedLang(?string $preferedLang): self
    {
        $this->preferedLang = $preferedLang;

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

    public function isReport(): ?bool
    {
        return $this->report;
    }

    public function isBypassHumanAuth(): ?bool
    {
        return $this->bypassHumanAuth;
    }

    /**
     * @return ?Collection<int, Groups>
     */
    public function getGroups(): ?Collection
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
        $this->groups->removeElement($group);

        return $this;
    }

    /**
     * @return ?Collection<int, Wblist>
     */
    public function getWbLists(): ?Collection
    {
        return $this->wbLists;
    }

    public function addWbList(Wblist $wbList): self
    {
        if (!$this->wbLists->contains($wbList)) {
            $this->wbLists[] = $wbList;
            $wbList->setRid($this);
        }

        return $this;
    }

    public function removeWbList(Wblist $wbList): self
    {
        if ($this->wbLists->removeElement($wbList)) {
            // set the owning side to null (unless already changed)
            if ($wbList->getRid() === $this) {
                $wbList->setRid(null);
            }
        }

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

    public function getOffice365PrincipalName(): ?string
    {
        return $this->office365PrincipalName;
    }

    public function setOffice365PrincipalName(?string $office365PrincipalName): static
    {
        $this->office365PrincipalName = $office365PrincipalName;

        return $this;
    }

    public function getSenderRateLimit(): ?SenderRateLimit
    {
        return $this->senderRateLimit;
    }

    public function setSenderRateLimit(SenderRateLimit $limits): self
    {
        $this->senderRateLimit = $limits;

        return $this;
    }

    /**
     * @return ?array<int, array<string, int>>
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
     * @return Collection<int, User>
     */
    public function getAliases(): ?Collection
    {
        return $this->aliases;
    }

    public function addAlias(User $alias): self
    {
        if (!$this->aliases->contains($alias)) {
            $this->aliases[] = $alias;
        }

        return $this;
    }

    public function removeAlias(User $alias): self
    {
        $this->aliases->removeElement($alias);

        return $this;
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles()) || in_array('ROLE_SUPER_ADMIN', $this->getRoles());
    }
}
