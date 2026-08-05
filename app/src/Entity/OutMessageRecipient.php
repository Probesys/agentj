<?php

namespace App\Entity;

use App\Amavis\ContentType;
use App\Amavis\DeliveryStatus;
use App\Amavis\MessageStatus;
use App\Repository\OutMessageRecipientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'out_msgrcpt')]
#[ORM\Index(name: 'out_msgrcpt_idx_mail_id', columns: ['mail_id'])]
#[ORM\Index(name: 'out_msgrcpt_idx_rid', columns: ['rid'])]
#[ORM\Index(name: 'out_msgrcpt_idx_status_id', columns: ['status_id'])]
#[ORM\Entity(repositoryClass: OutMessageRecipientRepository::class)]
class OutMessageRecipient extends BaseMessageRecipient
{
    #[ORM\ManyToOne(inversedBy: 'messageRecipients')]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id', onDelete: 'CASCADE')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag', onDelete: 'CASCADE')]
    private ?OutMessage $message;

    public function getMessage(): ?OutMessage
    {
        return $this->message;
    }

    public function setMessage(?OutMessage $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Return the status of the outgoing message.
     *
     * As status is not consolidated for outgoing messages (thus, not set),
     * this method return a coherent status based on Amavis fields.
     *
     * @see \App\Repository\MessageRecipientRepository::consolidateStatus
     */
    public function getStatus(): int
    {
        if ($this->getDs() === DeliveryStatus::PASS) {
            return MessageStatus::AUTHORIZED;
        }

        if ($this->getContent() === ContentType::VIRUS) {
            return MessageStatus::VIRUS;
        }

        if ($this->getBl() === 'Y') {
            return MessageStatus::BANNED;
        }

        if ($this->getContent() === ContentType::SPAM) {
            return MessageStatus::SPAMMED;
        }

        // There is no "untreated" status for outgoing messages. If we get
        // there, it's probably another kind of error. If you need to know
        // more, check the "content" field.
        return MessageStatus::ERROR;
    }
}
