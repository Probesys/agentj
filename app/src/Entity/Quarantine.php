<?php

namespace App\Entity;

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

    public function getMailText(): mixed
    {
        return $this->mailText;
    }

    public function setMailText(mixed $mailText): self
    {
        $this->mailText = $mailText;

        return $this;
    }
}
