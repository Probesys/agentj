<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\DBAL\Types\Types;
use App\Repository\DomainRepository;

/**
 * Domain
 */
#[ORM\Table(name: 'domain')]
#[ORM\Entity(repositoryClass: DomainRepository::class)]
#[UniqueEntity('domain')]
class Domain
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'domain', type: 'string', length: 255, unique: true, nullable: false)]
    private string $domain;

    #[ORM\Column(name: 'srv_smtp', type: 'string', length: 255, nullable: false)]
    private string $srvSmtp;

    #[ORM\Column(name: 'datemod', type: 'datetime', nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $datemod;

    #[ORM\Column(name: 'active', type: 'boolean', nullable: false)]
    private bool $active;

    #[ORM\Column(name: 'transport', type: 'string', length: 255, nullable: false)]
    private string $transport;

    #[ORM\Column(name: 'message', type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(name: 'mailmessage', type: Types::TEXT, nullable: true)]
    private ?string $mailmessage = null;

    #[ORM\Column(name: 'mail_alert', type: Types::TEXT, nullable: true)]
    private ?string $messageAlert = null;

    #[ORM\Column(name: 'level', type: 'float', nullable: true)]
    private ?float $level = 20;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Policy')]
    #[ORM\JoinColumn(name: 'policy_id', nullable: true)]
    private ?Policy $policy;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\User', mappedBy: 'domains')]
    private Collection $users;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $confirmCaptchaMessage;

    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $humanAuthenticationStylesheet = '';

    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $humanAuthenticationFooter = '';

    /**
     * @var Collection<int, Groups>
     */
    #[ORM\OneToMany(targetEntity: 'App\Entity\Groups', mappedBy: 'domain', orphanRemoval: true)]
    private Collection $groups;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $logo;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $mailAuthenticationSender;

    #[ORM\Column(type: 'string', length: 5, nullable: true)]
    private ?string $defaultLang;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $smtpPort;

    /**
     * @var Collection<int, Connector>
     */
    #[ORM\OneToMany(targetEntity: Connector::class, mappedBy: 'domain')]
    private Collection $connectors;

    /**
     * @var Collection<int, DailyStat>
     */
    #[ORM\OneToMany(mappedBy: 'domain', targetEntity: DailyStat::class)]
    private Collection $dailyStats;

    #[ORM\OneToOne(
        targetEntity: 'App\Entity\DomainKey',
        inversedBy: 'domain',
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    private ?DomainKey $domainKeys;

    /**
     * @var Collection<int, DomainRelay>
     */
    #[ORM\OneToMany(mappedBy: 'domain', targetEntity: DomainRelay::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $domainRelays;

    /**
     * @var array<int, array<string, int>>
     */
    #[ORM\Column(nullable: true)]
    private ?array $quota = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $sendUserAlerts = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $sendUserMailAlerts = false;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->datemod = new \DateTime();
        $this->groups = new ArrayCollection();
        $this->connectors = new ArrayCollection();
        $this->dailyStats = new ArrayCollection();
        $this->domainKeys = new DomainKey();
        $this->domainRelays = new ArrayCollection(); # ip addresses
    }


    public function __toString()
    {
        return $this->domain;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getSrvSmtp(): ?string
    {
        return $this->srvSmtp;
    }

    public function setSrvSmtp(string $srvSmtp): self
    {
        $this->srvSmtp = $srvSmtp;

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

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getTransport(): ?string
    {
        return $this->transport;
    }

    public function setTransport(string $transport): self
    {
        $this->transport = $transport;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }


    public function getMailmessage(): ?string
    {
        return $this->mailmessage;
    }

    public function setMailmessage(?string $mailmessage): self
    {
        $this->mailmessage = $mailmessage;

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
            $user->addDomain($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeDomain($this);
        }

        return $this;
    }

    public function getConfirmCaptchaMessage(): ?string
    {
        return $this->confirmCaptchaMessage;
    }

    public function setConfirmCaptchaMessage(?string $confirmCaptchaMessage): self
    {
        $this->confirmCaptchaMessage = $confirmCaptchaMessage;

        return $this;
    }

    public function getHumanAuthenticationStylesheet(): string
    {
        return $this->humanAuthenticationStylesheet;
    }

    public function setHumanAuthenticationStylesheet(string $humanAuthenticationStylesheet): static
    {
        $this->humanAuthenticationStylesheet = $humanAuthenticationStylesheet;

        return $this;
    }

    public function getHumanAuthenticationFooter(): string
    {
        return $this->humanAuthenticationFooter;
    }

    public function setHumanAuthenticationFooter(string $humanAuthenticationFooter): static
    {
        $this->humanAuthenticationFooter = $humanAuthenticationFooter;

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
            $group->setDomain($this);
        }

        return $this;
    }

    public function removeGroup(Groups $group): self
    {
        if ($this->groups->contains($group)) {
            $this->groups->removeElement($group);
            // set the owning side to null (unless already changed)
            if ($group->getDomain() === $this) {
                $group->setDomain(null);
            }
        }

        return $this;
    }

    public function getMessageAlert(): ?string
    {
        return $this->messageAlert;
    }

    public function setMessageAlert(?string $messageAlert): self
    {
        $this->messageAlert = $messageAlert;

        return $this;
    }

    public function getLevel(): ?float
    {
        return $this->level;
    }

    public function setLevel(?float $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getMailAuthenticationSender(): ?string
    {
        return $this->mailAuthenticationSender;
    }

    public function setMailAuthenticationSender(?string $mailAuthenticationSender): self
    {
        $this->mailAuthenticationSender = $mailAuthenticationSender;

        return $this;
    }

    public function getDefaultLang(): ?string
    {
        return $this->defaultLang;
    }

    public function setDefaultLang(?string $defaultLang): self
    {
        $this->defaultLang = $defaultLang;

        return $this;
    }

    public function getSmtpPort(): ?int
    {
        return $this->smtpPort;
    }

    public function setSmtpPort(?int $smtpPort): self
    {
        $this->smtpPort = $smtpPort;

        return $this;
    }

    /**
     * @return Collection<int, Connector>
     */
    public function getConnectors(): Collection
    {
        return $this->connectors;
    }

    public function addConnector(Connector $connector): self
    {
        if (!$this->connectors->contains($connector)) {
            $this->connectors[] = $connector;
            $connector->setDomain($this);
        }

        return $this;
    }

    public function removeConnector(Connector $connector): self
    {
        if ($this->connectors->removeElement($connector)) {
            // set the owning side to null (unless already changed)
            if ($connector->getDomain() === $this) {
                $connector->setDomain(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DailyStat>
     */
    public function getDailyStats(): Collection
    {
        return $this->dailyStats;
    }

    public function addDailyStat(DailyStat $dailyStat): self
    {
        if (!$this->dailyStats->contains($dailyStat)) {
            $this->dailyStats->add($dailyStat);
            $dailyStat->setDomain($this);
        }

        return $this;
    }

    public function removeDailyStat(DailyStat $dailyStat): self
    {
        if ($this->dailyStats->removeElement($dailyStat)) {
            // set the owning side to null (unless already changed)
            if ($dailyStat->getDomain() === $this) {
                $dailyStat->setDomain(null);
            }
        }

        return $this;
    }

    public function getDomainKeys(): ?DomainKey
    {
        return $this->domainKeys;
    }

    public function setDomainKeys(?DomainKey $domainKeys): self
    {
        $this->domainKeys = $domainKeys;

        return $this;
    }

    /**
     * @return Collection<int, DomainRelay>
     */
    public function getDomainRelays(): Collection
    {
        return $this->domainRelays;
    }

    public function addDomainRelay(DomainRelay $domainRelay): static
    {
        if (!$this->domainRelays->contains($domainRelay)) {
            $this->domainRelays->add($domainRelay);
            $domainRelay->setDomain($this);
        }

        return $this;
    }

    public function removeDomainRelay(DomainRelay $domainRelay): static
    {
        if ($this->domainRelays->removeElement($domainRelay)) {
            // set the owning side to null (unless already changed)
            if ($domainRelay->getDomain() === $this) {
                $domainRelay->setDomain(null);
            }
        }

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

    public function getSendUserAlerts(): bool
    {
        return $this->sendUserAlerts;
    }

    public function setSendUserAlerts(bool $sendUserAlerts): static
    {
        $this->sendUserAlerts = $sendUserAlerts;
        return $this;
    }

    public function getSendUserMailAlerts(): bool
    {
        return $this->sendUserMailAlerts;
    }

    public function setSendUserMailAlerts(bool $sendUserMailAlerts): static
    {
        $this->sendUserMailAlerts = $sendUserMailAlerts;
        return $this;
    }

    public function hasIMAPConnector(): bool
    {
        foreach ($this->getConnectors() as $connector) {
            if ($connector->getType() === 'IMAP') {
                return true;
            }
        }
        return false;
    }
}
