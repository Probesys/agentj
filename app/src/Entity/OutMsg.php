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
    private int $partition_tag = 0;

    #[ORM\Id]
    #[ORM\Column(type: 'binary', length: 255)]
    private mixed $mail_id = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $status_id;

    #[ORM\Column(type: 'bigint', nullable: true, options: ['unsigned' => true])]
    private mixed $sid;

    #[ORM\Column(type: 'binary', length: 255, nullable: true)]
    private mixed $secret_id;

    #[ORM\Column(type: 'string', length: 20)]
    private string $am_id;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $time_num;

    #[ORM\Column(type: 'string', length: 16)]
    private string $time_iso;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $policy = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $client_addr = null;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $size;

    #[ORM\Column(type: 'string', length: 1, options: ['default' => ''])]
    private string $originating;

    #[ORM\Column(type: 'string', length: 1, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: 'string', length: 1, nullable: true)]
    private ?string $quar_type = null;

    #[ORM\Column(type: 'binary', length: 255, nullable: true)]
    private mixed $quar_loc;

    #[ORM\Column(type: 'string', length: 1, nullable: true)]
    private ?string $dsn_sent = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $spam_level = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $message_id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $from_addr = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $host;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    private ?int $validate_captcha = null;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $send_captcha = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message_error = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $is_mlist = null;

    #[ORM\Column(type: 'boolean')]
    private bool $processed_user = false;

    #[ORM\Column(type: 'boolean')]
    private bool $processed_admin = false;

    // Getters and setters for the fields you need

    public function getPartitionTag(): ?int
    {
        return $this->partition_tag;
    }

    public function getMailId(): mixed
    {
        if ($this->mail_id === null) {
            return null;
        }

        $mailId = is_resource($this->mail_id) ? stream_get_contents($this->mail_id) : $this->mail_id;

        return bin2hex($mailId);
    }

    public function getStatusId(): ?int
    {
        return $this->status_id;
    }

    public function getSid(): ?int
    {
        return $this->sid;
    }

    public function getSecretId(): mixed
    {
        return $this->secret_id !== null ? bin2hex($this->secret_id) : null;
    }

    public function getAmId(): ?string
    {
        return $this->am_id;
    }

    public function getTimeNum(): ?int
    {
        return $this->time_num;
    }

    public function getTimeIso(): ?string
    {
        return $this->time_iso;
    }

    public function getPolicy(): ?string
    {
        return $this->policy;
    }

    public function getClientAddr(): ?string
    {
        return $this->client_addr;
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
        return $this->quar_type;
    }

    public function getQuarLoc(): mixed
    {
        return $this->quar_loc;
    }

    public function getDsnSent(): ?string
    {
        return $this->dsn_sent;
    }

    public function getSpamLevel(): ?float
    {
        return $this->spam_level;
    }

    public function getMessageId(): ?string
    {
        return $this->message_id;
    }

    public function getFromAddr(): ?string
    {
        return $this->from_addr;
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
        return $this->validate_captcha;
    }

    public function getSendCaptcha(): ?int
    {
        return $this->send_captcha;
    }

    public function getMessageError(): ?string
    {
        return $this->message_error;
    }

    public function getIsMlist(): ?bool
    {
        return $this->is_mlist;
    }

    public function isProcessedUser(): ?bool
    {
        return $this->processed_user;
    }

    public function setProcessedUser(bool $processed_user): self
    {
        $this->processed_user = $processed_user;
        return $this;
    }

    public function isProcessedAdmin(): ?bool
    {
        return $this->processed_admin;
    }

    public function setProcessedAdmin(bool $processed_admin): self
    {
        $this->processed_admin = $processed_admin;
        return $this;
    }

    public function setStatusId(?int $status_id): static
    {
        $this->status_id = $status_id;

        return $this;
    }

    public function setSid(?string $sid): static
    {
        $this->sid = $sid;

        return $this;
    }

    public function setSecretId(mixed $secret_id): static
    {
        $this->secret_id = $secret_id;

        return $this;
    }

    public function setAmId(string $am_id): static
    {
        $this->am_id = $am_id;

        return $this;
    }

    public function setTimeNum(int $time_num): static
    {
        $this->time_num = $time_num;

        return $this;
    }

    public function setTimeIso(string $time_iso): static
    {
        $this->time_iso = $time_iso;

        return $this;
    }

    public function setPolicy(?string $policy): static
    {
        $this->policy = $policy;

        return $this;
    }

    public function setClientAddr(?string $client_addr): static
    {
        $this->client_addr = $client_addr;

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

    public function setQuarType(?string $quar_type): static
    {
        $this->quar_type = $quar_type;

        return $this;
    }

    public function setQuarLoc(mixed $quar_loc): static
    {
        $this->quar_loc = $quar_loc;

        return $this;
    }

    public function setDsnSent(?string $dsn_sent): static
    {
        $this->dsn_sent = $dsn_sent;

        return $this;
    }

    public function setSpamLevel(?float $spam_level): static
    {
        $this->spam_level = $spam_level;

        return $this;
    }

    public function setMessageId(?string $message_id): static
    {
        $this->message_id = $message_id;

        return $this;
    }

    public function setFromAddr(?string $from_addr): static
    {
        $this->from_addr = $from_addr;

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

    public function setValidateCaptcha(int $validate_captcha): static
    {
        $this->validate_captcha = $validate_captcha;

        return $this;
    }

    public function setSendCaptcha(int $send_captcha): static
    {
        $this->send_captcha = $send_captcha;

        return $this;
    }

    public function setMessageError(?string $message_error): static
    {
        $this->message_error = $message_error;

        return $this;
    }

    public function isMlist(): ?bool
    {
        return $this->is_mlist;
    }

    public function setIsMlist(?bool $is_mlist): static
    {
        $this->is_mlist = $is_mlist;

        return $this;
    }
}