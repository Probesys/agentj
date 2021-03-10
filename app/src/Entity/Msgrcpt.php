<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Msgrcpt
 *
 * @ORM\Table(name="msgrcpt", indexes={@ORM\Index(name="msgrcpt_idx_mail_id", columns={"mail_id"}), @ORM\Index(name="msgrcpt_idx_rid", columns={"rid"})})
 * @ORM\Entity(repositoryClass="App\Repository\MsgrcptRepository")
 */
class Msgrcpt
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
     * @ORM\Column(name="rseqnum", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $rseqnum = '0';

    /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Maddr")
    * @ORM\JoinColumn(name="rid", nullable=true)
    */
    private $rid;         
    
    /**
     * @var string
     *
     * @ORM\Column(name="is_local", type="string", length=1, nullable=false, options={"fixed"=true})
     */
    private $isLocal = '';

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", length=1, nullable=false, options={"fixed"=true})
     */
    private $content = '';

    /**
     * @var string
     *
     * @ORM\Column(name="ds", type="string", length=1, nullable=false, options={"fixed"=true})
     */
    private $ds;

    /**
     * @var string
     *
     * @ORM\Column(name="rs", type="string", length=1, nullable=false, options={"fixed"=true})
     */
    private $rs;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bl", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $bl = '';

    /**
     * @var string|null
     *
     * @ORM\Column(name="wl", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $wl = '';

    /**
     * @var float|null
     *
     * @ORM\Column(name="bspam_level", type="float", precision=10, scale=0, nullable=true)
     */
    private $bspamLevel;

    /**
     * @var string|null
     *
     * @ORM\Column(name="smtp_resp", type="string", length=255, nullable=true)
     */
    private $smtpResp = '';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MessageStatus", inversedBy="msgrcpts")
     */
    private $status;

    /**
     * @ORM\Column(type="integer", type="integer", nullable=false, options={"unsigned"=true,"default":0})
     */
    private $sendCaptcha = 0;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $amavisOutput;

    public function getPartitionTag(): ?int
    {
        return $this->partitionTag;
    }

    public function getMailId()
    {
        return $this->mailId;
    }

    public function getRseqnum(): ?int
    {
        return $this->rseqnum;
    }

    public function getIsLocal(): ?string
    {
        return $this->isLocal;
    }

    public function setIsLocal(string $isLocal): self
    {
        $this->isLocal = $isLocal;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getDs(): ?string
    {
        return $this->ds;
    }

    public function setDs(string $ds): self
    {
        $this->ds = $ds;

        return $this;
    }

    public function getRs(): ?string
    {
        return $this->rs;
    }

    public function setRs(string $rs): self
    {
        $this->rs = $rs;

        return $this;
    }

    public function getBl(): ?string
    {
        return $this->bl;
    }

    public function setBl(?string $bl): self
    {
        $this->bl = $bl;

        return $this;
    }

    public function getWl(): ?string
    {
        return $this->wl;
    }

    public function setWl(?string $wl): self
    {
        $this->wl = $wl;

        return $this;
    }

    public function getBspamLevel(): ?float
    {
        return $this->bspamLevel;
    }

    public function setBspamLevel(?float $bspamLevel): self
    {
        $this->bspamLevel = $bspamLevel;

        return $this;
    }

    public function getSmtpResp(): ?string
    {
        return $this->smtpResp;
    }

    public function setSmtpResp(?string $smtpResp): self
    {
        $this->smtpResp = $smtpResp;

        return $this;
    }

    public function getRid(): ?Maddr
    {
        return $this->rid;
    }

    public function setRid(?Maddr $rid): self
    {
        $this->rid = $rid;

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

    public function getSendCaptcha(): ?int
    {
        return $this->sendCaptcha;
    }

    public function setSendCaptcha(int $sendCaptcha): self
    {
        $this->sendCaptcha = $sendCaptcha;

        return $this;
    }

    public function getAmavisOutput(): ?string
    {
        return $this->amavisOutput;
    }

    public function setAmavisOutput(string $amavisOutput): self
    {
        $this->amavisOutput = $amavisOutput;

        return $this;
    }


}
