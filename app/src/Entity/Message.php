<?php

namespace App\Entity;

use App\Amavis\SpamIndicator;
use App\Repository\MessageRepository;
use Carbon\Exceptions\InvalidFormatException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use TypeError;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Message as Email;

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

    /**
     * @return list<array{indicator: SpamIndicator, score: ?float, rules: list<string>}>
     */
    public function getSpamIndicators(): array
    {
        if (!$this->isInQuarantine()) {
            return [];
        }

        try {
            $header = (string) $this->getQuarantineEmail()->getHeader()->get('x_spam_status')->first();
        } catch (InvalidFormatException | InvalidMessageDateException | MessageContentFetchingException | TypeError) {
            // Spam details are optional: malformed mail must not prevent displaying the message information.
            return [];
        }

        $rule = '[A-Za-z0-9_]+';
        $score = '[-+]?(?:\d+\.?\d*|\.\d+)';
        $test = "{$rule}(?:={$score})?";

        // Amavis provides tests=[A=1, B=-0.1]; native SpamAssassin uses tests=A,B without scores.
        if (!preg_match("/(?:^|\s)tests=(?:\[([^\]]*)\]|({$test}(?:\s*,\s*{$test})*))/i", $header, $matches)) {
            return [];
        }

        $rawTests = $matches[1] !== '' ? $matches[1] : ($matches[2] ?? '');
        $tests = [];
        foreach (preg_split('/\s*,\s*/', trim($rawTests), flags: PREG_SPLIT_NO_EMPTY) ?: [] as $rawTest) {
            if (!preg_match("/^({$rule})(?:=({$score}))?$/", $rawTest, $testMatches)) {
                continue;
            }

            $name = strtoupper($testMatches[1]);
            if ($name !== 'NONE') {
                $tests[$name] = isset($testMatches[2]) ? (float) $testMatches[2] : null;
            }
        }

        return SpamIndicator::fromTests($tests);
    }
}
