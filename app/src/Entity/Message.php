<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Webklex\PHPIMAP\Message as Email;

/**
 * Message
 */
#[ORM\Table(name: 'msgs')]
#[ORM\Index(name: 'msgs_idx_sid', columns: ['sid'])]
#[ORM\Index(name: 'msgs_idx_mess_id', columns: ['message_id'])]
#[ORM\Index(name: 'msgs_idx_time_num', columns: ['time_num'])]
#[ORM\Index(name: 'msgs_idx_time_iso', columns: ['time_iso'])]
#[ORM\Index(name: 'msgs_idx_mail_id', columns: ['mail_id'])]
#[ORM\Index(name: 'msgs_idx_from_addr', columns: ['from_addr'])]
#[ORM\Index(name: 'idx_msgs_quar_type', columns: ['quar_type'])]
#[ORM\Index(name: 'msgs_idx_send_captcha_time_num', columns: ['send_captcha', 'time_num'])]
#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message extends BaseMessage
{
    /** @var Collection<int, MessageRecipient> $messageRecipients */
    #[ORM\OneToMany(mappedBy: 'message', targetEntity: MessageRecipient::class)]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag')]
    private Collection $messageRecipients;

    /** @var Collection<int, Quarantine> $quarantineChunks */
    #[ORM\OneToMany(mappedBy: 'message', targetEntity: Quarantine::class)]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag')]
    private Collection $quarantineChunks;

    public function __construct()
    {
        $this->messageRecipients = new ArrayCollection();
        $this->quarantineChunks = new ArrayCollection();
    }

    /**
     * @return Collection<int, MessageRecipient>
     */
    public function getMessageRecipients(): Collection
    {
        return $this->messageRecipients;
    }

    /**
     * @return Collection<int, Quarantine>
     */
    public function getQuarantineChunks(): Collection
    {
        return $this->quarantineChunks;
    }

    public function isInQuarantine(): bool
    {
        return !$this->quarantineChunks->isEmpty();
    }

    public function getQuarantineContent(): string
    {
        $rawMail = "";

        foreach ($this->getQuarantineChunks() as $chunk) {
            $rawMail .= $chunk->getMailText() ?? '';
        }

        return mb_convert_encoding($rawMail, 'UTF-8', 'auto') ?: $rawMail;
    }

    public function getQuarantineEmail(): Email
    {
        return Email::fromString($this->getQuarantineContent());
    }
}
