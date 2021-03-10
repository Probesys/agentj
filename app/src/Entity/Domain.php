<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Domain
 *
 * @ORM\Table(name="domain")
 * @ORM\Entity(repositoryClass="App\Repository\DomainRepository")
 * @UniqueEntity("domain")
 */
class Domain
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="domain", type="string", length=255,unique=true, nullable=false)
     */
    private $domain;

    /**
     * @var string
     *
     * @ORM\Column(name="srv_smtp", type="string", length=255, nullable=false)
     */
    private $srvSmtp;

    /**
     * @var string
     *
     * @ORM\Column(name="srv_imap", type="string", length=255, nullable=false)
     */
    private $srvImap;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datemod", type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $datemod;

    /**
     * @var int
     *
     * @ORM\Column(name="imap_port", type="integer", nullable=false, options={"default"="143"})
     */
    private $imap_port;

    /**
     * @var string
     *
     * @ORM\Column(name="imap_flag", type="string", length=255, nullable=false)
     */
    private $imap_flag;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;    

    /**
     * @var string
     *
     * @ORM\Column(name="transport", type="string", length=255, nullable=false)
     */
    private $transport;    

    /**
     * @var text
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     *
     */
    private $message;

    /**
     * @var text
     *
     * @ORM\Column(name="mailmessage", type="text", nullable=true)
     *
     */
    private $mailmessage;

    /**
     * @var text
     *
     * @ORM\Column(name="mail_alert", type="text", nullable=true)
     *
     */
    private $message_alert;
    
    /**
     * @var string
     *
     * @ORM\Column(name="level", type="float", nullable=true)
     */
    private $level = 20;    
    
    /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Captcha")
    * @ORM\JoinColumn(name="captcha_id", nullable=true)
    */
    private $captcha;    
    
    /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Policy")
    * @ORM\JoinColumn(name="policy_id", nullable=true)
    */
    private $policy;     

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="domains")
     */
    private $users;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $confirmCaptchaMessage;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Groups", mappedBy="domain", orphanRemoval=true)
     */
    private $groups;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $logo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mailAuthenticationSender;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $imapNoValidateCert;

  
    
    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->datemod = new \DateTime();
        $this->groups = new ArrayCollection();
    }

    public function __toString(){
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

    public function getSrvImap(): ?string
    {
        return $this->srvImap;
    }

    public function setSrvImap(string $srvImap): self
    {
        $this->srvImap = $srvImap;

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

    public function getImapPort(): ?int
    {
        return $this->imap_port;
    }

    public function setImapPort(int $imap_port): self
    {
        $this->imap_port = $imap_port;

        return $this;
    }

    public function getImapFlag(): ?string
    {
        return $this->imap_flag;
    }

    public function setImapFlag(string $imap_flag): self
    {
        $this->imap_flag = $imap_flag;

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

    public function getCaptcha(): ?Captcha
    {
        return $this->captcha;
    }

    public function setCaptcha(?Captcha $captcha): self
    {
        $this->captcha = $captcha;

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
     * @return Collection|User[]
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
        return $this->message_alert;
    }

    public function setMessageAlert(?string $message_alert): self
    {
        $this->message_alert = $message_alert;

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

    public function getImapNoValidateCert(): ?bool
    {
        return $this->imapNoValidateCert;
    }

    public function setImapNoValidateCert(?bool $imapNoValidateCert): self
    {
        $this->imapNoValidateCert = $imapNoValidateCert;

        return $this;
    }


}
