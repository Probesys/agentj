<?php
// src/Entity/OutMsg.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\OutMsgRepository')]
#[ORM\Table(name: 'out_msgs')]
class OutMsg
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private $partition_tag;

    #[ORM\Id]
    #[ORM\Column(type: 'binary', length: 255)]
    private $mail_id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $status_id;

    #[ORM\Column(type: 'bigint', nullable: true, options: ['unsigned' => true])]
    private $sid;

    #[ORM\Column(type: 'binary', length: 255, nullable: true)]
    private $secret_id;

    #[ORM\Column(type: 'string', length: 20)]
    private $am_id;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private $time_num;

    #[ORM\Column(type: 'string', length: 16)]
    private $time_iso;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $policy;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $client_addr;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private $size;

    #[ORM\Column(type: 'string', length: 1, options: ['default' => ''])]
    private $originating;

    #[ORM\Column(type: 'string', length: 1, nullable: true)]
    private $content;

    #[ORM\Column(type: 'string', length: 1, nullable: true)]
    private $quar_type;

    #[ORM\Column(type: 'binary', length: 255, nullable: true)]
    private $quar_loc;

    #[ORM\Column(type: 'string', length: 1, nullable: true)]
    private $dsn_sent;

    #[ORM\Column(type: 'float', nullable: true)]
    private $spam_level;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $message_id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $from_addr;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $subject;

    #[ORM\Column(type: 'string', length: 255)]
    private $host;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    private $validate_captcha;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private $send_captcha;

    #[ORM\Column(type: 'text', nullable: true)]
    private $message_error;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $is_mlist;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $processed;

    // Getters and setters for the fields you need

    public function getPartitionTag(): ?int
    {
        return $this->partition_tag;
    }

    public function getMailId(): ?string
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

    public function getSecretId(): ?string
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

    public function getQuarLoc(): ?string
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

    public function isProcessed(): ?bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): self
    {
        $this->processed = $processed;
        return $this;
    }
}