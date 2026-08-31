<?php

namespace App\Tests;

use App\Entity\Address;
use App\Entity\User;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\MessageFactory;
use App\Tests\Factory\MessageRecipientFactory;
use App\Tests\Factory\QuarantineFactory;
use App\Util\Url;

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
        ?string $subject = 'test',
        ?int $status = null,
    ): string {
        $mailId = bin2hex(random_bytes(8));

        $message = MessageFactory::new()->create([
            'partitionTag' => 0,
            'mailId' => $mailId,
            'senderAddress' => $sender,
            'subject' => $subject,
            'fromAddr' => $sender->getEmail(),
            'status' => $status,
            'sendCaptcha' => 0,
        ]);

        MessageRecipientFactory::new()->create([
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
            'amavisReleaseStartedAt' => null,
            'amavisReleaseEndedAt' => null,
        ]);

        $mailText = QuarantineFactory::generateMailText($mailId, [
            'subject' => $subject,
            'from' => $sender->getEmail(),
            'to' => [$recipient->getEmail()],
        ]);

        QuarantineFactory::new()->create([
            'partitionTag' => 0,
            'mailId' => $mailId,
            'message' => $message,
            'chunkInd' => 0,
            'mailText' => $mailText,
        ]);

        return $mailId;
    }
}
