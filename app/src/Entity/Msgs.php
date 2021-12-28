<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Msgs
 *
 * @ORM\Table(name="msgs", indexes={@ORM\Index(name="msgs_idx_sid", columns={"sid"}), @ORM\Index(name="msgs_idx_mess_id", columns={"message_id"}), @ORM\Index(name="msgs_idx_time_num", columns={"time_num"}), @ORM\Index(name="msgs_idx_time_iso", columns={"time_iso"}), @ORM\Index(name="msgs_idx_mail_id", columns={"mail_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\MsgsRepository")
 */
class Msgs
{
    /**
     * @var int
     *
     * @ORM\Column(name="partition_tag", type="integer", nullable=false)
     * @ORM\Id
     */
    private $partitionTag = '0';

    /**
     * @var binary
     *
     * @ORM\Column(name="mail_id", type="binary", nullable=false)
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="App\Entity\Msgrcpt", inversedBy="msgs", cascade={"persist", "remove"},fetch="EAGER")
     */
    private $mailId;

    /**
     * @var binary|null
     *
     * @ORM\Column(name="secret_id", type="binary", nullable=true)
     */
    private $secretId = '';

    /**
     * @var string
     *
     * @ORM\Column(name="am_id", type="string", length=20, nullable=false)
     */
    private $amId;

    /**
     * @var int
     *
     * @ORM\Column(name="time_num", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $timeNum;

    /**
     * @var string
     *
     * @ORM\Column(name="time_iso", type="string", length=16, nullable=false, options={"fixed"=true})
     */
    private $timeIso;

    /**
     * @var string|null
     *
     * @ORM\Column(name="policy", type="string", length=255, nullable=true)
     */
    private $policy = '';

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_addr", type="string", length=255, nullable=true)
     */
    private $clientAddr = '';

    /**
     * @var int
     *
     * @ORM\Column(name="size", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $size;

    /**
     * @var string
     *
     * @ORM\Column(name="originating", type="string", length=1, nullable=false, options={"fixed"=true,"default":" "})
     */
    private $originating = '';

    /**
     * @var string|null
     *
     * @ORM\Column(name="content", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $content;

    /**
     * @var string|null
     *
     * @ORM\Column(name="quar_type", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $quarType;

    /**
     * @var binary|null
     *
     * @ORM\Column(name="quar_loc", type="binary", nullable=true)
     */
    private $quarLoc = '';

    /**
     * @var string|null
     *
     * @ORM\Column(name="dsn_sent", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $dsnSent;

    /**
     * @var float|null
     *
     * @ORM\Column(name="spam_level", type="float", precision=10, scale=0, nullable=true)
     */
    private $spamLevel;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message_id", type="string", length=255, nullable=true)
     */
    private $messageId = '';

    /**
     * @var string|null
     *
     * @ORM\Column(name="from_addr", type="string", length=255, nullable=true)
     */
    private $fromAddr = '';

    /**
     * @var string|null
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    private $subject = '';

    /**
     * @var string
     *
     * @ORM\Column(name="host", type="string", length=255, nullable=false)
     */
    private $host;

    /**
     * @var int
     *
     * @ORM\Column(name="validate_captcha", type="integer", nullable=true, options={"unsigned"=true,"default":0})
     */
    private $validate_captcha;

    /**
    * @ORM\ManyToOne(targetEntity="App\Entity\MessageStatus")
    * @ORM\JoinColumn(name="status_id", nullable=true)
    */
    private $status;

    /**
     * @var \Maddr
     *
     * @ORM\ManyToOne(targetEntity="Maddr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sid", referencedColumnName="id")
     * })
     */
    private $sid;

    /**
     * @var int
     *
     * @ORM\Column(name="send_captcha", type="integer", nullable=false, options={"unsigned"=true,"default":0})
     */
    private $sendCaptcha = 0;

    /**
     * @var text
     *
     * @ORM\Column(name="message_error", type="text", nullable=true)
     *
     */
    private $messageError;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isMlist;


    public function getPartitionTag(): ?int
    {
        return $this->partitionTag;
    }

    public function getSecretId()
    {
        return $this->secretId;
    }

    public function setSecretId($secretId): self
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

    public function getQuarLoc()
    {
        return $this->quarLoc;
    }

    public function setQuarLoc($quarLoc): self
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

    public function getMailId()
    {
        return $this->mailId;
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
}
