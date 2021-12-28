<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Users
 *
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(
 *     fields={"email"},
 *     ignoreNull=true,
 * )
 */
class User implements UserInterface, \Serializable
{

  /**
   * @var int
   *
   * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
    private $id;

  /**
   * @var int
   *
   * @ORM\Column(name="priority", type="integer", nullable=false, options={"default"="7"})
   */
    private $priority = '7';

  /**
   * @var binary
   *
   * @ORM\Column(name="email", type="binary", nullable=true, unique=true)
   */
    private $email;

  /**
   * @var string|null
   *
   * @ORM\Column(name="fullname", type="string", length=255, nullable=true)
   */
    private $fullname;

  /**
   * @var string|null
   *
   * @ORM\Column(name="username", type="string", length=255, nullable=true)
   */
    private $username;

  /**
   * @var string|null
   *
   * @ORM\Column(name="local", type="string", length=1, nullable=true, options={"fixed"=true})
   */
    private $local;

  /**
   * @var string
   *
   * @ORM\Column(name="password", type="string", length=255, nullable=true)
   */
    private $password;

  /**
   * @var string
   *
   * @ORM\Column(name="roles", type="text", length=0, nullable=true)
   */
    private $roles;

  /**
   * @var string|null
   *
   * @ORM\Column(name="emailRecovery", type="string", length=255, nullable=true)
   */
    private $emailRecovery;

  /**
   * @var string|null
   *
   * @ORM\Column(name="imapLogin", type="string", length=255, nullable=true)
   */
    private $imapLogin;


  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Policy")
   * @ORM\JoinColumn(name="policy_id", nullable=true)
   */
    private $policy;


  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\Domain", inversedBy="users")
   * @ORM\JoinTable(name="users_domains")
   */
    private $domains;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Domain")
   * @ORM\JoinColumn(name="domain_id", nullable=true, onDelete="CASCADE")
   */
    private $domain;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Groups", inversedBy="users")
   * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
   */
    private $groups;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\User")
   * @ORM\JoinColumn(name="original_user_id", nullable=true, onDelete="CASCADE")
   */
    private $originalUser;

  /**
   * @ORM\Column(type="boolean", nullable=true)
   */
    private $report;

  /**
   * @ORM\ManyToMany(targetEntity=User::class, inversedBy="ownedSharedBoxes")
   */
    private $sharedWith;

  /**
   * @ORM\ManyToMany(targetEntity=User::class, mappedBy="sharedWith")
   */
    private $ownedSharedBoxes;

  /**
   * @ORM\Column(type="integer", nullable=true)
   */
    private $dateLastReport;

  /**
   * @ORM\Column(type="boolean", nullable=true)
   */
    private $bypassHumanAuth;

    public function __construct()
    {

        $this->domains = new ArrayCollection();
        $this->sharedWith = new ArrayCollection();
        $this->owners = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->email ? stream_get_contents($this->email, -1, 0) : "";
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEmail()
    {
        return $this->email;
    }

    public function getEmailFromRessource()
    {
        return stream_get_contents($this->email, -1, 0);
    }

    public function setEmail($email): self
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

    public function getRoles()
    {
        $roles = json_decode($this->roles);

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
        if ($this->username == '') {
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
   * @return Collection|Domain[]
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

  /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize([
            $this->id,
  //            $this->priority,
            $this->email,
            $this->password,
            $this->fullname,
  //            $this->local,
  //            $this->roles,
            $this->username,
  //  //          $this->policy,
  //            $this->domain,
  //            $this->groups,
  ////            $this->emailRecovery,
        ]);
    }

  /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
  //            $this->priority,
            $this->email,
            $this->password,
            $this->fullname,
  //            $this->local,
  //            $this->roles,
            $this->username,
  //  //          $this->policy,
  //            $this->domain,
  //            $this->groups,
  ////            $this->emailRecovery,
            ) = unserialize($serialized);
    }

    public function getGroups(): ?Groups
    {
        return $this->groups;
    }

    public function setGroups(?Groups $groups): self
    {
        $this->groups = $groups;

        return $this;
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
   * @return Collection|self[]
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
   * @return Collection|self[]
   */
    public function getOwnedSharedBoxes(): Collection
    {
        return $this->ownedSharedBoxes;
    }

    public function addOwnedSharedBox(self $ownedSharedBox): self
    {
        if (!$this->ownedSharedBoxes->contains($ownedSharedBox)) {
            $this->ownedSharedBoxes[] = $ownedSharedBox;
            $owner->addSharedWith($this);
        }

        return $this;
    }

    public function removeOwnedSharedBox(self $ownedSharedBox): self
    {
        if ($this->owners->contains($ownedSharedBox)) {
            $this->owners->removeElement($ownedSharedBox);
            $ownedSharedBoxes->removeSharedWith($this);
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
}
