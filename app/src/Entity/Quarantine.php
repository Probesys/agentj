<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Quarantine
 *
 * @ORM\Table(name="quarantine")
 * @ORM\Entity
 */
class Quarantine
{
    /**
     * @var int
     *
     * @ORM\Column(name="partition_tag", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $partitionTag = '0';

    /**
     * @var binary
     *
     * @ORM\Column(name="mail_id", type="binary", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $mailId;

    /**
     * @var int
     *
     * @ORM\Column(name="chunk_ind", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $chunkInd;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_text", type="blob", length=65535, nullable=false)
     */
    private $mailText;

    public function getPartitionTag(): ?int
    {
        return $this->partitionTag;
    }

    public function getMailId()
    {
        return $this->mailId;
    }

    public function getChunkInd(): ?int
    {
        return $this->chunkInd;
    }

    public function getMailText()
    {
        return $this->mailText;
    }

    public function setMailText($mailText): self
    {
        $this->mailText = $mailText;

        return $this;
    }
}
