<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Mime\Address;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Maddr;
use App\Entity\Msgrcpt;
use App\Entity\MessageStatus;
use App\Repository\MsgsRepository;

/**
 * Msgs
 */
#[ORM\Table(name: 'msgs')]
#[ORM\Index(name: 'msgs_idx_sid', columns: ['sid'])]
#[ORM\Index(name: 'msgs_idx_mess_id', columns: ['message_id'])]
#[ORM\Index(name: 'msgs_idx_time_num', columns: ['time_num'])]
#[ORM\Index(name: 'msgs_idx_time_iso', columns: ['time_iso'])]
#[ORM\Index(name: 'msgs_idx_mail_id', columns: ['mail_id'])]
#[ORM\Index(name: 'idx_msgs_quar_type', columns: ['quar_type'])]
#[ORM\Entity(repositoryClass: MsgsRepository::class)]
class Msgs
{

    #[ORM\Column(name: 'partition_tag', type: 'integer', nullable: false)]
    #[ORM\Id]
    private int $partitionTag = 0;

    #[ORM\Column(name: 'mail_id', type: Types::BINARY, nullable: false)]
    #[ORM\Id]
    private mixed $mailId = null;

    #[ORM\Column(name: 'secret_id', type: Types::BINARY, nullable: true)]
    private mixed $secretId = null;

    #[ORM\Column(name: 'am_id', type: 'string', length: 20, nullable: false)]
    private string $amId;

    #[ORM\Column(name: 'time_num', type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $timeNum;

    #[ORM\Column(name: 'time_iso', type: 'string', length: 16, nullable: false, options: ['fixed' => true])]
    private string $timeIso;

    #[ORM\Column(name: 'policy', type: 'string', length: 255, nullable: true)]
    private ?string $policy = '';

    #[ORM\Column(name: 'client_addr', type: 'string', length: 255, nullable: true)]
    private ?string $clientAddr = '';

    #[ORM\Column(name: 'size', type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $size;

    #[ORM\Column(name: 'originating', type: 'string', length: 1, nullable: false, options: ['fixed' => true, 'default' => ''])]
    private string $originating = '';

    #[ORM\Column(name: 'content', type: 'string', length: 1, nullable: true, options: ['fixed' => true])]
    private ?string $content;

    #[ORM\Column(name: 'quar_type', type: 'string', length: 1, nullable: true, options: ['fixed' => true])]
    private ?string $quarType;

    #[ORM\Column(name: 'quar_loc', type: Types::BINARY, nullable: true)]
    private mixed $quarLoc = null;

    #[ORM\Column(name: 'dsn_sent', type: 'string', length: 1, nullable: true, options: ['fixed' => true])]
    private ?string $dsnSent;

    #[ORM\Column(name: 'spam_level', type: 'float', precision: 10, scale: 0, nullable: true)]
    private ?float $spamLevel;

    #[ORM\Column(name: 'message_id', type: 'string', length: 255, nullable: true)]
    private ?string $messageId = '';

    #[ORM\Column(name: 'from_addr', type: 'string', length: 255, nullable: true)]
    private ?string $fromAddr = '';

    #[ORM\Column(name: 'subject', type: 'string', length: 255, nullable: true)]
    private ?string $subject = '';

    #[ORM\Column(name: 'host', type: 'string', length: 255, nullable: false)]
    private string $host;

    /** @var Collection<int, MsgRcpt> $msgRcpts */
    #[ORM\OneToMany(mappedBy: 'msgs', targetEntity: MsgRcpt::class)]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag')]
    private Collection $msgRcpts;

    #[ORM\Column(name: 'validate_captcha', type: 'integer', nullable: true, options: ['unsigned' => true, 'default' => 0])]
    private int $validate_captcha;

    #[ORM\ManyToOne(targetEntity: MessageStatus::class)]
    #[ORM\JoinColumn(name: 'status_id', nullable: true)]
    private ?MessageStatus $status;

    #[ORM\JoinColumn(name: 'sid', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Maddr::class)]
    private ?Maddr $sid;

    #[ORM\Column(name: 'send_captcha', type: 'integer', nullable: false, options: ['unsigned' => true, 'default' => 0])]
    private int $sendCaptcha = 0;

    #[ORM\Column(name: 'message_error', type: 'text', nullable: true)]
    private ?string $messageError;

    //If true it means that the message is from a mailing list
    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isMlist;

    public function __construct()
    {
        $this->msgRcpts = new ArrayCollection();
    }

    public function getPartitionTag(): ?int
    {
        return $this->partitionTag;
    }

    public function getSecretId(): mixed
    {
        return $this->secretId;
    }

    public function setSecretId(mixed $secretId): self
    {
        $this->secretId = $secretId;

        return $this;
    }

    public function getAmId(): ?string
    {
        return $this->amId;
    }

    public function setAmId(string $amId): self
    {
        $this->amId = $amId;

        return $this;
    }

    public function getTimeNum(): ?int
    {
        return $this->timeNum;
    }

    public function setTimeNum(int $timeNum): self
    {
        $this->timeNum = $timeNum;

        return $this;
    }

    public function getTimeIso(): ?string
    {
        return $this->timeIso;
    }

    public function setTimeIso(string $timeIso): self
    {
        $this->timeIso = $timeIso;

        return $this;
    }

    public function getPolicy(): ?string
    {
        return $this->policy;
    }

    public function setPolicy(?string $policy): self
    {
        $this->policy = $policy;

        return $this;
    }

    public function getClientAddr(): ?string
    {
        return $this->clientAddr;
    }

    public function setClientAddr(?string $clientAddr): self
    {
        $this->clientAddr = $clientAddr;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getOriginating(): ?string
    {
        return $this->originating;
    }

    public function setOriginating(string $originating): self
    {
        $this->originating = $originating;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getQuarType(): ?string
    {
        return $this->quarType;
    }

    public function setQuarType(?string $quarType): self
    {
        $this->quarType = $quarType;

        return $this;
    }

    public function getQuarLoc(): mixed
    {
        return $this->quarLoc;
    }

    public function setQuarLoc(mixed $quarLoc): self
    {
        $this->quarLoc = $quarLoc;

        return $this;
    }

    public function getDsnSent(): ?string
    {
        return $this->dsnSent;
    }

    public function setDsnSent(?string $dsnSent): self
    {
        $this->dsnSent = $dsnSent;

        return $this;
    }

    public function getSpamLevel(): ?float
    {
        return $this->spamLevel;
    }

    public function setSpamLevel(?float $spamLevel): self
    {
        $this->spamLevel = $spamLevel;

        return $this;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function setMessageId(?string $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getFromAddr(): ?string
    {
        return $this->fromAddr;
    }

    public function getFromMimeAddress(): ?Address
    {
        try {
            return Address::create($this->fromAddr ?? '');
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    public function setFromAddr(?string $fromAddr): self
    {
        $this->fromAddr = $fromAddr;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getSid(): ?Maddr
    {
        return $this->sid;
    }

    public function setSid(?Maddr $sid): self
    {
        $this->sid = $sid;

        return $this;
    }

    public function getStatus(): ?MessageStatus
    {
        return $this->status;
    }

    public function setStatus(?MessageStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getMailId(): mixed
    {
        return $this->mailId;
    }

    public function getMailIdAsString(): string
    {
        $strMailId = $this->mailId;
        if (is_resource($strMailId)) {
            $strMailId = stream_get_contents($this->mailId, -1, 0);
            rewind($this->mailId);
        }
        return $strMailId;
    }

    public function getSendCaptcha(): ?int
    {
        return $this->sendCaptcha;
    }

    public function setSendCaptcha(int $sendCaptcha): self
    {
        $this->sendCaptcha = $sendCaptcha;

        return $this;
    }

    public function getMessageError(): ?string
    {
        return $this->messageError;
    }

    public function setMessageError(?string $messageError): self
    {
        $this->messageError = $messageError;

        return $this;
    }

    public function getValidateCaptcha(): ?int
    {
        return $this->validate_captcha;
    }

    public function setValidateCaptcha(?int $validate_captcha): self
    {
        $this->validate_captcha = $validate_captcha;

        return $this;
    }

    public function getIsMlist(): ?bool
    {
        return $this->isMlist;
    }

    public function setIsMlist(?bool $isMlist): self
    {
        $this->isMlist = $isMlist;

        return $this;
    }

    /**
     * @return Collection<int, MsgRcpt>
     */
    public function getMsgRcpts(): Collection
    {
        return $this->msgRcpts;
    }

    public function addMsgRcpt(MsgRcpt $msgRcpt): self
    {
        if (!$this->msgRcpts->contains($msgRcpt)) {
            $this->msgRcpts[] = $msgRcpt;
            $msgRcpt->setMsgs($this);
        }

        return $this;
    }

    public function removeMsgRcpt(MsgRcpt $msgRcpt): self
    {
        if ($this->msgRcpts->removeElement($msgRcpt)) {
            // set the owning side to null (unless already changed)
            if ($msgRcpt->getMsgs() === $this) {
                $msgRcpt->setMsgs(null);
            }
        }

        return $this;
    }
}
