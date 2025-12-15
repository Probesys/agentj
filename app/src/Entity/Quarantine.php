<?php

namespace App\Entity;

use App\Util\ResourceHelper;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

/**
 * Quarantine
 */
#[ORM\Table(name: 'quarantine')]
#[ORM\Entity]
class Quarantine
{
    #[ORM\Column(name: 'partition_tag', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $partitionTag = 0;

    #[ORM\Column(name: 'mail_id', type: Types::BINARY, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private mixed $mailId = null;

    #[ORM\Column(name: 'chunk_ind', type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $chunkInd = 0;

    #[ORM\Column(name: 'mail_text', type: 'blob', length: 65535, nullable: false)]
    private mixed $mailText = null;

    #[ORM\ManyToOne(inversedBy: 'quarantineChunks')]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id', onDelete: 'CASCADE')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag', onDelete: 'CASCADE')]
    private Msgs $message;

    public function getPartitionTag(): int
    {
        return $this->partitionTag;
    }

    public function getMailId(): mixed
    {
        return $this->mailId;
    }

    public function getChunkInd(): int
    {
        return $this->chunkInd;
    }

    public function getMailText(): ?string
    {
        return ResourceHelper::toString($this->mailText);
    }

    public function getMessage(): Msgs
    {
        return $this->message;
    }

    public function setMessage(Msgs $message): self
    {
        $this->message = $message;

        return $this;
    }
}
