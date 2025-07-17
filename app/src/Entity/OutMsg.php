<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'out_msgs')]
class OutMsg
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $partitionTag = 0;

    #[ORM\Id]
    #[ORM\Column(type: 'binary', length: 255)]
    private mixed $mailId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $statusId;

    #[ORM\Column(type: 'bigint', nullable: true, options: ['unsigned' => true])]
    private mixed $sid;

    #[ORM\Column(type: 'binary', length: 255, nullable: true)]
    private mixed $secretId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $amId;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $timeNum;

    #[ORM\Column(type: 'string', length: 16)]
    private string $timeIso;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $policy = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $clientAddr = null;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $size;

    #[ORM\Column(type: 'string', length: 1, options: ['default' => ''])]
    private string $originating;

    #[ORM\Column(type: 'string', length: 1, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: 'string', length: 1, nullable: true)]
    private ?string $quarType = null;

    #[ORM\Column(type: 'binary', length: 255, nullable: true)]
    private mixed $quarLoc;

    #[ORM\Column(type: 'string', length: 1, nullable: true)]
    private ?string $dsnSent = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $spamLevel = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $messageId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $fromAddr = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $host;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    private ?int $validateCaptcha = null;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $sendCaptcha = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $messageError = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isMlist = null;

    #[ORM\Column(type: 'boolean')]
    private bool $processedUser = false;

    #[ORM\Column(type: 'boolean')]
    private bool $processedAdmin = false;

    public function getPartitionTag(): ?int
    {
        return $this->partitionTag;
    }

    public function getMailId(): mixed
    {
        if ($this->mailId === null) {
            return null;
        }

        $mailId = is_resource($this->mailId) ? stream_get_contents($this->mailId) : $this->mailId;

        return bin2hex($mailId);
    }

    public function getStatusId(): ?int
    {
        return $this->statusId;
    }

    public function getSid(): ?int
    {
        return $this->sid;
    }

    public function getSecretId(): mixed
    {
        return $this->secretId !== null ? bin2hex($this->secretId) : null;
    }

    public function getAmId(): ?string
    {
        return $this->amId;
    }

    public function getTimeNum(): ?int
    {
        return $this->timeNum;
    }

    public function getTimeIso(): ?string
    {
        return $this->timeIso;
    }

    public function getPolicy(): ?string
    {
        return $this->policy;
    }

    public function getClientAddr(): ?string
    {
        return $this->clientAddr;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getOriginating(): ?string
    {
        return $this->originating;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getQuarType(): ?string
    {
        return $this->quarType;
    }

    public function getQuarLoc(): mixed
    {
        return $this->quarLoc;
    }

    public function getDsnSent(): ?string
    {
        return $this->dsnSent;
    }

    public function getSpamLevel(): ?float
    {
        return $this->spamLevel;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function getFromAddr(): ?string
    {
        return $this->fromAddr;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getValidateCaptcha(): ?int
    {
        return $this->validateCaptcha;
    }

    public function getSendCaptcha(): ?int
    {
        return $this->sendCaptcha;
    }

    public function getMessageError(): ?string
    {
        return $this->messageError;
    }

    public function getIsMlist(): ?bool
    {
        return $this->isMlist;
    }

    public function isProcessedUser(): ?bool
    {
        return $this->processedUser;
    }

    public function setProcessedUser(bool $processedUser): self
    {
        $this->processedUser = $processedUser;
        return $this;
    }

    public function isProcessedAdmin(): ?bool
    {
        return $this->processedAdmin;
    }

    public function setProcessedAdmin(bool $processedAdmin): self
    {
        $this->processedAdmin = $processedAdmin;
        return $this;
    }

    public function setStatusId(?int $statusId): static
    {
        $this->statusId = $statusId;

        return $this;
    }

    public function setSid(?string $sid): static
    {
        $this->sid = $sid;

        return $this;
    }

    public function setSecretId(mixed $secretId): static
    {
        $this->secretId = $secretId;

        return $this;
    }

    public function setAmId(string $amId): static
    {
        $this->amId = $amId;

        return $this;
    }

    public function setTimeNum(int $timeNum): static
    {
        $this->timeNum = $timeNum;

        return $this;
    }

    public function setTimeIso(string $timeIso): static
    {
        $this->timeIso = $timeIso;

        return $this;
    }

    public function setPolicy(?string $policy): static
    {
        $this->policy = $policy;

        return $this;
    }

    public function setClientAddr(?string $clientAddr): static
    {
        $this->clientAddr = $clientAddr;

        return $this;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function setOriginating(string $originating): static
    {
        $this->originating = $originating;

        return $this;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function setQuarType(?string $quarType): static
    {
        $this->quarType = $quarType;

        return $this;
    }

    public function setQuarLoc(mixed $quarLoc): static
    {
        $this->quarLoc = $quarLoc;

        return $this;
    }

    public function setDsnSent(?string $dsnSent): static
    {
        $this->dsnSent = $dsnSent;

        return $this;
    }

    public function setSpamLevel(?float $spamLevel): static
    {
        $this->spamLevel = $spamLevel;

        return $this;
    }

    public function setMessageId(?string $messageId): static
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function setFromAddr(?string $fromAddr): static
    {
        $this->fromAddr = $fromAddr;

        return $this;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function setHost(string $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function setValidateCaptcha(int $validateCaptcha): static
    {
        $this->validateCaptcha = $validateCaptcha;

        return $this;
    }

    public function setSendCaptcha(int $sendCaptcha): static
    {
        $this->sendCaptcha = $sendCaptcha;

        return $this;
    }

    public function setMessageError(?string $messageError): static
    {
        $this->messageError = $messageError;

        return $this;
    }

    public function isMlist(): ?bool
    {
        return $this->isMlist;
    }

    public function setIsMlist(?bool $isMlist): static
    {
        $this->isMlist = $isMlist;

        return $this;
    }
}
