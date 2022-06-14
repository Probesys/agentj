<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Policy
 *
 * @ORM\Table(name="policy")
 * @ORM\Entity(repositoryClass="App\Repository\PolicyRepository")
 */
class Policy
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="policy_name", type="string", length=32, nullable=true)
     */
    private $policyName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="virus_lover", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $virusLover;

    /**
     * @var string|null
     *
     * @ORM\Column(name="spam_lover", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $spamLover;

    /**
     * @var string|null
     *
     * @ORM\Column(name="unchecked_lover", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $uncheckedLover;

    /**
     * @var string|null
     *
     * @ORM\Column(name="banned_files_lover", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $bannedFilesLover;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bad_header_lover", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $badHeaderLover;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bypass_virus_checks", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $bypassVirusChecks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bypass_spam_checks", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $bypassSpamChecks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bypass_banned_checks", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $bypassBannedChecks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bypass_header_checks", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $bypassHeaderChecks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="virus_quarantine_to", type="string", length=64, nullable=true)
     */
    private $virusQuarantineTo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="spam_quarantine_to", type="string", length=64, nullable=true)
     */
    private $spamQuarantineTo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="banned_quarantine_to", type="string", length=64, nullable=true)
     */
    private $bannedQuarantineTo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="unchecked_quarantine_to", type="string", length=64, nullable=true)
     */
    private $uncheckedQuarantineTo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bad_header_quarantine_to", type="string", length=64, nullable=true)
     */
    private $badHeaderQuarantineTo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="clean_quarantine_to", type="string", length=64, nullable=true)
     */
    private $cleanQuarantineTo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="archive_quarantine_to", type="string", length=64, nullable=true)
     */
    private $archiveQuarantineTo;

    /**
     * @var float|null
     *
     * @ORM\Column(name="spam_tag_level", type="float", precision=10, scale=0, nullable=true)
     */
    private $spamTagLevel;

    /**
     * @var float|null
     *
     * @ORM\Column(name="spam_tag2_level", type="float", precision=10, scale=0, nullable=true)
     */
    private $spamTag2Level;

    /**
     * @var float|null
     *
     * @ORM\Column(name="spam_tag3_level", type="float", precision=10, scale=0, nullable=true)
     */
    private $spamTag3Level;

    /**
     * @var float|null
     *
     * @ORM\Column(name="spam_kill_level", type="float", precision=10, scale=0, nullable=true)
     */
    private $spamKillLevel;

    /**
     * @var float|null
     *
     * @ORM\Column(name="spam_dsn_cutoff_level", type="float", precision=10, scale=0, nullable=true)
     */
    private $spamDsnCutoffLevel;

    /**
     * @var float|null
     *
     * @ORM\Column(name="spam_quarantine_cutoff_level", type="float", precision=10, scale=0, nullable=true)
     */
    private $spamQuarantineCutoffLevel;

    /**
     * @var string|null
     *
     * @ORM\Column(name="addr_extension_virus", type="string", length=64, nullable=true)
     */
    private $addrExtensionVirus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="addr_extension_spam", type="string", length=64, nullable=true)
     */
    private $addrExtensionSpam;

    /**
     * @var string|null
     *
     * @ORM\Column(name="addr_extension_banned", type="string", length=64, nullable=true)
     */
    private $addrExtensionBanned;

    /**
     * @var string|null
     *
     * @ORM\Column(name="addr_extension_bad_header", type="string", length=64, nullable=true)
     */
    private $addrExtensionBadHeader;

    /**
     * @var string|null
     *
     * @ORM\Column(name="warnvirusrecip", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $warnvirusrecip;

    /**
     * @var string|null
     *
     * @ORM\Column(name="warnbannedrecip", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $warnbannedrecip;

    /**
     * @var string|null
     *
     * @ORM\Column(name="warnbadhrecip", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $warnbadhrecip;

    /**
     * @var string|null
     *
     * @ORM\Column(name="newvirus_admin", type="string", length=64, nullable=true)
     */
    private $newvirusAdmin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="virus_admin", type="string", length=64, nullable=true)
     */
    private $virusAdmin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="banned_admin", type="string", length=64, nullable=true)
     */
    private $bannedAdmin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bad_header_admin", type="string", length=64, nullable=true)
     */
    private $badHeaderAdmin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="spam_admin", type="string", length=64, nullable=true)
     */
    private $spamAdmin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="spam_subject_tag", type="string", length=64, nullable=true)
     */
    private $spamSubjectTag;

    /**
     * @var string|null
     *
     * @ORM\Column(name="spam_subject_tag2", type="string", length=64, nullable=true)
     */
    private $spamSubjectTag2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="spam_subject_tag3", type="string", length=64, nullable=true)
     */
    private $spamSubjectTag3;

    /**
     * @var int|null
     *
     * @ORM\Column(name="message_size_limit", type="integer", nullable=true)
     */
    private $messageSizeLimit;

    /**
     * @var string|null
     *
     * @ORM\Column(name="banned_rulenames", type="string", length=64, nullable=true)
     */
    private $bannedRulenames;

    /**
     * @var string|null
     *
     * @ORM\Column(name="disclaimer_options", type="string", length=64, nullable=true)
     */
    private $disclaimerOptions;

    /**
     * @var string|null
     *
     * @ORM\Column(name="forward_method", type="string", length=64, nullable=true)
     */
    private $forwardMethod;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sa_userconf", type="string", length=64, nullable=true)
     */
    private $saUserconf;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sa_username", type="string", length=64, nullable=true)
     */
    private $saUsername;

    public function __toString(): string
    {
        return $this->policyName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPolicyName(): ?string
    {
        return $this->policyName;
    }

    public function setPolicyName(?string $policyName): self
    {
        $this->policyName = $policyName;

        return $this;
    }

    public function getVirusLover(): ?string
    {
        return $this->virusLover;
    }

    public function setVirusLover(?string $virusLover): self
    {
        $this->virusLover = $virusLover;

        return $this;
    }

    public function getSpamLover(): ?string
    {
        return $this->spamLover;
    }

    public function setSpamLover(?string $spamLover): self
    {
        $this->spamLover = $spamLover;

        return $this;
    }

    public function getUncheckedLover(): ?string
    {
        return $this->uncheckedLover;
    }

    public function setUncheckedLover(?string $uncheckedLover): self
    {
        $this->uncheckedLover = $uncheckedLover;

        return $this;
    }

    public function getBannedFilesLover(): ?string
    {
        return $this->bannedFilesLover;
    }

    public function setBannedFilesLover(?string $bannedFilesLover): self
    {
        $this->bannedFilesLover = $bannedFilesLover;

        return $this;
    }

    public function getBadHeaderLover(): ?string
    {
        return $this->badHeaderLover;
    }

    public function setBadHeaderLover(?string $badHeaderLover): self
    {
        $this->badHeaderLover = $badHeaderLover;

        return $this;
    }

    public function getBypassVirusChecks(): ?string
    {
        return $this->bypassVirusChecks;
    }

    public function setBypassVirusChecks(?string $bypassVirusChecks): self
    {
        $this->bypassVirusChecks = $bypassVirusChecks;

        return $this;
    }

    public function getBypassSpamChecks(): ?string
    {
        return $this->bypassSpamChecks;
    }

    public function setBypassSpamChecks(?string $bypassSpamChecks): self
    {
        $this->bypassSpamChecks = $bypassSpamChecks;

        return $this;
    }

    public function getBypassBannedChecks(): ?string
    {
        return $this->bypassBannedChecks;
    }

    public function setBypassBannedChecks(?string $bypassBannedChecks): self
    {
        $this->bypassBannedChecks = $bypassBannedChecks;

        return $this;
    }

    public function getBypassHeaderChecks(): ?string
    {
        return $this->bypassHeaderChecks;
    }

    public function setBypassHeaderChecks(?string $bypassHeaderChecks): self
    {
        $this->bypassHeaderChecks = $bypassHeaderChecks;

        return $this;
    }

    public function getVirusQuarantineTo(): ?string
    {
        return $this->virusQuarantineTo;
    }

    public function setVirusQuarantineTo(?string $virusQuarantineTo): self
    {
        $this->virusQuarantineTo = $virusQuarantineTo;

        return $this;
    }

    public function getSpamQuarantineTo(): ?string
    {
        return $this->spamQuarantineTo;
    }

    public function setSpamQuarantineTo(?string $spamQuarantineTo): self
    {
        $this->spamQuarantineTo = $spamQuarantineTo;

        return $this;
    }

    public function getBannedQuarantineTo(): ?string
    {
        return $this->bannedQuarantineTo;
    }

    public function setBannedQuarantineTo(?string $bannedQuarantineTo): self
    {
        $this->bannedQuarantineTo = $bannedQuarantineTo;

        return $this;
    }

    public function getUncheckedQuarantineTo(): ?string
    {
        return $this->uncheckedQuarantineTo;
    }

    public function setUncheckedQuarantineTo(?string $uncheckedQuarantineTo): self
    {
        $this->uncheckedQuarantineTo = $uncheckedQuarantineTo;

        return $this;
    }

    public function getBadHeaderQuarantineTo(): ?string
    {
        return $this->badHeaderQuarantineTo;
    }

    public function setBadHeaderQuarantineTo(?string $badHeaderQuarantineTo): self
    {
        $this->badHeaderQuarantineTo = $badHeaderQuarantineTo;

        return $this;
    }

    public function getCleanQuarantineTo(): ?string
    {
        return $this->cleanQuarantineTo;
    }

    public function setCleanQuarantineTo(?string $cleanQuarantineTo): self
    {
        $this->cleanQuarantineTo = $cleanQuarantineTo;

        return $this;
    }

    public function getArchiveQuarantineTo(): ?string
    {
        return $this->archiveQuarantineTo;
    }

    public function setArchiveQuarantineTo(?string $archiveQuarantineTo): self
    {
        $this->archiveQuarantineTo = $archiveQuarantineTo;

        return $this;
    }

    public function getSpamTagLevel(): ?float
    {
        return $this->spamTagLevel;
    }

    public function setSpamTagLevel(?float $spamTagLevel): self
    {
        $this->spamTagLevel = $spamTagLevel;

        return $this;
    }

    public function getSpamTag2Level(): ?float
    {
        return $this->spamTag2Level;
    }

    public function setSpamTag2Level(?float $spamTag2Level): self
    {
        $this->spamTag2Level = $spamTag2Level;

        return $this;
    }

    public function getSpamTag3Level(): ?float
    {
        return $this->spamTag3Level;
    }

    public function setSpamTag3Level(?float $spamTag3Level): self
    {
        $this->spamTag3Level = $spamTag3Level;

        return $this;
    }

    public function getSpamKillLevel(): ?float
    {
        return $this->spamKillLevel;
    }

    public function setSpamKillLevel(?float $spamKillLevel): self
    {
        $this->spamKillLevel = $spamKillLevel;

        return $this;
    }

    public function getSpamDsnCutoffLevel(): ?float
    {
        return $this->spamDsnCutoffLevel;
    }

    public function setSpamDsnCutoffLevel(?float $spamDsnCutoffLevel): self
    {
        $this->spamDsnCutoffLevel = $spamDsnCutoffLevel;

        return $this;
    }

    public function getSpamQuarantineCutoffLevel(): ?float
    {
        return $this->spamQuarantineCutoffLevel;
    }

    public function setSpamQuarantineCutoffLevel(?float $spamQuarantineCutoffLevel): self
    {
        $this->spamQuarantineCutoffLevel = $spamQuarantineCutoffLevel;

        return $this;
    }

    public function getAddrExtensionVirus(): ?string
    {
        return $this->addrExtensionVirus;
    }

    public function setAddrExtensionVirus(?string $addrExtensionVirus): self
    {
        $this->addrExtensionVirus = $addrExtensionVirus;

        return $this;
    }

    public function getAddrExtensionSpam(): ?string
    {
        return $this->addrExtensionSpam;
    }

    public function setAddrExtensionSpam(?string $addrExtensionSpam): self
    {
        $this->addrExtensionSpam = $addrExtensionSpam;

        return $this;
    }

    public function getAddrExtensionBanned(): ?string
    {
        return $this->addrExtensionBanned;
    }

    public function setAddrExtensionBanned(?string $addrExtensionBanned): self
    {
        $this->addrExtensionBanned = $addrExtensionBanned;

        return $this;
    }

    public function getAddrExtensionBadHeader(): ?string
    {
        return $this->addrExtensionBadHeader;
    }

    public function setAddrExtensionBadHeader(?string $addrExtensionBadHeader): self
    {
        $this->addrExtensionBadHeader = $addrExtensionBadHeader;

        return $this;
    }

    public function getWarnvirusrecip(): ?string
    {
        return $this->warnvirusrecip;
    }

    public function setWarnvirusrecip(?string $warnvirusrecip): self
    {
        $this->warnvirusrecip = $warnvirusrecip;

        return $this;
    }

    public function getWarnbannedrecip(): ?string
    {
        return $this->warnbannedrecip;
    }

    public function setWarnbannedrecip(?string $warnbannedrecip): self
    {
        $this->warnbannedrecip = $warnbannedrecip;

        return $this;
    }

    public function getWarnbadhrecip(): ?string
    {
        return $this->warnbadhrecip;
    }

    public function setWarnbadhrecip(?string $warnbadhrecip): self
    {
        $this->warnbadhrecip = $warnbadhrecip;

        return $this;
    }

    public function getNewvirusAdmin(): ?string
    {
        return $this->newvirusAdmin;
    }

    public function setNewvirusAdmin(?string $newvirusAdmin): self
    {
        $this->newvirusAdmin = $newvirusAdmin;

        return $this;
    }

    public function getVirusAdmin(): ?string
    {
        return $this->virusAdmin;
    }

    public function setVirusAdmin(?string $virusAdmin): self
    {
        $this->virusAdmin = $virusAdmin;

        return $this;
    }

    public function getBannedAdmin(): ?string
    {
        return $this->bannedAdmin;
    }

    public function setBannedAdmin(?string $bannedAdmin): self
    {
        $this->bannedAdmin = $bannedAdmin;

        return $this;
    }

    public function getBadHeaderAdmin(): ?string
    {
        return $this->badHeaderAdmin;
    }

    public function setBadHeaderAdmin(?string $badHeaderAdmin): self
    {
        $this->badHeaderAdmin = $badHeaderAdmin;

        return $this;
    }

    public function getSpamAdmin(): ?string
    {
        return $this->spamAdmin;
    }

    public function setSpamAdmin(?string $spamAdmin): self
    {
        $this->spamAdmin = $spamAdmin;

        return $this;
    }

    public function getSpamSubjectTag(): ?string
    {
        return $this->spamSubjectTag;
    }

    public function setSpamSubjectTag(?string $spamSubjectTag): self
    {
        $this->spamSubjectTag = $spamSubjectTag;

        return $this;
    }

    public function getSpamSubjectTag2(): ?string
    {
        return $this->spamSubjectTag2;
    }

    public function setSpamSubjectTag2(?string $spamSubjectTag2): self
    {
        $this->spamSubjectTag2 = $spamSubjectTag2;

        return $this;
    }

    public function getSpamSubjectTag3(): ?string
    {
        return $this->spamSubjectTag3;
    }

    public function setSpamSubjectTag3(?string $spamSubjectTag3): self
    {
        $this->spamSubjectTag3 = $spamSubjectTag3;

        return $this;
    }

    public function getMessageSizeLimit(): ?int
    {
        return $this->messageSizeLimit;
    }

    public function setMessageSizeLimit(?int $messageSizeLimit): self
    {
        $this->messageSizeLimit = $messageSizeLimit;

        return $this;
    }

    public function getBannedRulenames(): ?string
    {
        return $this->bannedRulenames;
    }

    public function setBannedRulenames(?string $bannedRulenames): self
    {
        $this->bannedRulenames = $bannedRulenames;

        return $this;
    }

    public function getDisclaimerOptions(): ?string
    {
        return $this->disclaimerOptions;
    }

    public function setDisclaimerOptions(?string $disclaimerOptions): self
    {
        $this->disclaimerOptions = $disclaimerOptions;

        return $this;
    }

    public function getForwardMethod(): ?string
    {
        return $this->forwardMethod;
    }

    public function setForwardMethod(?string $forwardMethod): self
    {
        $this->forwardMethod = $forwardMethod;

        return $this;
    }

    public function getSaUserconf(): ?string
    {
        return $this->saUserconf;
    }

    public function setSaUserconf(?string $saUserconf): self
    {
        $this->saUserconf = $saUserconf;

        return $this;
    }

    public function getSaUsername(): ?string
    {
        return $this->saUsername;
    }

    public function setSaUsername(?string $saUsername): self
    {
        $this->saUsername = $saUsername;

        return $this;
    }
}
