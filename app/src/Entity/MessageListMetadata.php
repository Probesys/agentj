<?php

namespace App\Entity;

use App\Repository\MessageListMetadataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Index(name: 'message_list_metadata_idx_list_id', columns: ['list_id'])]
#[ORM\Entity(repositoryClass: MessageListMetadataRepository::class, readOnly: true)]
class MessageListMetadata
{
    #[ORM\Column(name: 'partition_tag', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $partitionTag = 0;

    #[ORM\Column(name: 'mail_id', type: Types::BINARY, length: 255, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $mailId;

    #[ORM\OneToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id', onDelete: 'CASCADE')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag', onDelete: 'CASCADE')]
    private ?Message $message = null;

    #[ORM\Column(name: 'list_id', type: Types::STRING, length: 255, nullable: true)]
    private ?string $listId = null;


    #[ORM\Column(name: 'list_unsubscribe', type: Types::TEXT, nullable: true)]
    private ?string $listUnsubscribe = null;


    #[ORM\Column(name: 'list_unsubscribe_post', type: Types::STRING, length: 255, nullable: true)]
    private ?string $listUnsubscribePost = null;

    public function getPartitionTag(): int
    {
        return $this->partitionTag;
    }

    public function getMailId(): string
    {
        return $this->mailId;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function getListId(): ?string
    {
        return $this->listId;
    }

    public function getListUnsubscribe(): ?string
    {
        return $this->listUnsubscribe;
    }

    public function getListUnsubscribePost(): ?string
    {
        return $this->listUnsubscribePost;
    }
}
