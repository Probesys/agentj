<?php

namespace App\Tests;

use App\Entity\Address;
use App\Entity\Message;
use App\Entity\User;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\MessageFactory;
use App\Tests\Factory\MessageRecipientFactory;
use App\Tests\Factory\OutMessageFactory;
use App\Tests\Factory\OutMessageRecipientFactory;
use App\Tests\Factory\OutQuarantineFactory;
use App\Tests\Factory\QuarantineFactory;
use App\Util\Url;
use DateTime;

trait MessageHelper
{
    /**
     * @return array<int, Address>
     */
    private function setupAddresses(User $sender, User $recipient): array
    {
        $addrR = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient->getEmail(),
        ]);
        $addrS = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($sender->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $sender->getEmail(),
        ]);

        return [
            $addrS,
            $addrR,
        ];
    }

    private function setupMail(
        Address $sender,
        Address $recipient,
        bool $isInMessage = true,
        ?string $subject = 'test',
        ?int $status = null,
    ): Message {
        $mailId = bin2hex(random_bytes(8));

        $messageFactory = $isInMessage ? MessageFactory::class : OutMessageFactory::class;
        $message = $messageFactory::new()->create([
            'partitionTag' => 0,
            'mailId' => $mailId,
            'senderAddress' => $sender,
            'subject' => $subject,
            'fromAddr' => $sender->getEmail(),
            'status' => $status,
        ]);

        $messageRecipientFactory = $isInMessage ? MessageRecipientFactory::class : OutMessageRecipientFactory::class;
        $data = [
            'message' => $message,
            'partitionTag' => 0,
            'mailId' => $mailId,
            'status' => $status,
            'address' => $recipient,
            'rseqnum' => 1,
            'isLocal' => 'N',
            'content' => 'S',
            'ds' => 'D',
            'bl' => 'N',
            'wl' => 'N',
            'bspamLevel' => -1.2,
            'smtpResp' => '250 2.7.0 Ok, discarded, id=00045-01 - spam',
            'sendCaptcha' => 0,
            'amavisOutput' => null,
        ];
        if ($isInMessage) {
            $data['amavisReleaseStartedAt'] = null;
            $data['amavisReleaseEndedAt'] = null;
        }
        $messageRecipientFactory::new()->create($data);

        $quarantineFactory = $isInMessage ? QuarantineFactory::class : OutQuarantineFactory::class;
        $mailText = $quarantineFactory::generateMailText($mailId, [
            'subject' => $subject,
            'from' => $sender->getEmail(),
            'to' => [$recipient->getEmail()],
        ]);
        $quarantineFactory::new()->create([
            'partitionTag' => 0,
            'mailId' => $mailId,
            'message' => $message,
            'chunkInd' => 0,
            'mailText' => $mailText,
        ]);

        return $message;
    }

    /**
     * @param array<mixed> $attributes
     */
    public static function generateMailText(string $mailId, array $attributes = []): string
    {
        $date = $attributes['date'] ?? new DateTime();
        $body = $attributes['body'] ?? '';

        $headers = <<<TEXT
                Message-ID: {$mailId}\r
                Subject: {$attributes['subject']}\r
                From: <{$attributes['from']}>\r
                To: support@example.com\r
                Date: {$date->format(DATE_RFC1123)}\r
                Content-Type: text/html\r
                TEXT;

        if (isset($attributes['to'])) {
            $toString = implode(', ', $attributes['to']);
            $headers .= "\nTo: {$toString}\r";
        } else {
            $headers .= "\nTo: support@example.com\r";
        }

        $attributesHeaders = $attributes['headers'] ?? [];
        foreach ($attributesHeaders as $name => $value) {
            $headers .= "\n{$name}: {$value}\r";
        }

        return "{$headers}\n\r\n\r{$body}";
    }
}
