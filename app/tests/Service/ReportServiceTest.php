<?php

namespace App\Tests\Service;

use App\Amavis\MessageStatus;
use App\Entity\Address;
use App\Entity\Domain;
use App\Entity\MessageRecipient;
use App\Entity\User;
use App\Service\ReportService;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class ReportServiceTest extends WebTestCase
{
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    private KernelBrowser $client;
    private ReportService $reportService;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->reportService = $this->client->getContainer()->get(ReportService::class);
    }

    public function testGetMessageRecipientRetrievesOnlyUntreatedSpamUnderLimitButAlsoMailingList(): void
    {
        [
            'recipient' => $recipient,
            'standard' => $standard,
            'spamAccepted' => $spamAccepted,
            'list' => $list,
        ] = $this->setupMessages();

        $messageRecipients = $this->reportService->getMessageRecipients($recipient);

        self::assertCount(3, $messageRecipients);
        self::assertEqualsCanonicalizing(
            [
                $standard->getMailId(),
                $spamAccepted->getMailId(),
                $list->getMailId(),
            ],
            [
                $messageRecipients[0]->getMailId(),
                $messageRecipients[1]->getMailId(),
                $messageRecipients[2]->getMailId(),
            ],
        );
    }

    public function testGetMessageRecipientRetrievesOnlyUntreatedNotSpamButAlsoMailingList(): void
    {
        [
            'domain' => $domain,
            'recipient' => $recipient,
            'standard' => $standard,
            'list' => $list,
        ] = $this->setupMessages();
        // Reset report spam level to defaul (0)
        $domain->setReportSpamLevel(0);

        $messageRecipients = $this->reportService->getMessageRecipients($recipient);

        self::assertCount(2, $messageRecipients);
        self::assertEqualsCanonicalizing(
            [
                $standard->getMailId(),
                $list->getMailId(),
            ],
            [
                $messageRecipients[0]->getMailId(),
                $messageRecipients[1]->getMailId(),
            ],
        );
    }

    public function testGetStandardMessageRecipientRetrievesOnlyUntreatedNotSpamNotMailingList(): void
    {
        [
            'recipient' => $recipient,
            'standard' => $standard,
            'spamAccepted' => $spamAccepted,
        ] = $this->setupMessages();

        $messageRecipients = $this->reportService->getStandardMessageRecipients($recipient);

        self::assertCount(2, $messageRecipients);
        self::assertEqualsCanonicalizing(
            [
                $standard->getMailId(),
                $spamAccepted->getMailId(),
            ],
            [
                $messageRecipients[0]->getMailId(),
                $messageRecipients[1]->getMailId(),
            ],
        );
    }

    public function testGetStandardMessageRecipientDoesNotRetrieveStandardMessageOtherThanUntreatedStatus(): void
    {
        [
            'recipient' => $recipient,
            'senderAddress' => $senderAddress,
            'recipientAddress' => $recipientAddress,
        ] = $this->minimalSetup();
        $this->addMail(
            $senderAddress,
            $recipientAddress,
            MessageStatus::AUTHORIZED,
            spamScore: 0,
            isMailingList: false,
        );

        $messageRecipients = $this->reportService->getStandardMessageRecipients($recipient);

        self::assertCount(0, $messageRecipients);
    }

    public function testGetListMessageRecipientRetrievesOnlyUntreatedNotSpamMailingList(): void
    {
        [
            'recipient' => $recipient,
            'list' => $list,
        ] = $this->setupMessages();

        $messageRecipients = $this->reportService->getListMessageRecipients($recipient);

        self::assertCount(1, $messageRecipients);
        self::assertSame($messageRecipients[0]->getMailId(), $list->getMailId());
    }

    public function testGetListMessageRecipientRetrievesMailingListMessageIfSpamUnderSpamLimit(): void
    {
        [
            'recipient' => $recipient,
            'senderAddress' => $senderAddress,
            'recipientAddress' => $recipientAddress,
        ] = $this->minimalSetup();
        $this->addMail(
            $senderAddress,
            $recipientAddress,
            MessageStatus::SPAMMED,
            spamScore: 2,
            isMailingList: true,
        );

        $messageRecipients = $this->reportService->getListMessageRecipients($recipient);

        self::assertCount(1, $messageRecipients);
    }

    public function testGetListMessageRecipientDoesNotRetrieveMailingListMessageOtherThanUntreatedStatus(): void
    {
        [
            'recipient' => $recipient,
            'senderAddress' => $senderAddress,
            'recipientAddress' => $recipientAddress,
        ] = $this->minimalSetup();
        $this->addMail(
            $senderAddress,
            $recipientAddress,
            MessageStatus::AUTHORIZED,
            spamScore: 0,
            isMailingList: true,
        );

        $messageRecipients = $this->reportService->getListMessageRecipients($recipient);

        self::assertCount(0, $messageRecipients);
    }

    public function testGetListMessageRecipientDoesNotRetrieveMailingListMessageAboveSpamLimit(): void
    {
        [
            'recipient' => $recipient,
            'senderAddress' => $senderAddress,
            'recipientAddress' => $recipientAddress,
        ] = $this->minimalSetup();
        $this->addMail(
            $senderAddress,
            $recipientAddress,
            MessageStatus::SPAMMED,
            spamScore: 12,
            isMailingList: true,
        );

        $messageRecipients = $this->reportService->getListMessageRecipients($recipient);

        self::assertCount(0, $messageRecipients);
    }

    public function testGetSpamMessageRecipientRetrievesOnlySpamAboveDomainMaxSpamLevel(): void
    {
        [
            'recipient' => $recipient,
            'spamRejected' => $spamRejected,
        ] = $this->setupMessages();

        $messageRecipients = $this->reportService->getSpamMessageRecipients($recipient);

        self::assertCount(1, $messageRecipients);
        self::assertSame($messageRecipients[0]->getMailId(), $spamRejected->getMailId());
    }

    public function testGetSpamMessageRecipientDoesNotRetrieveSpamUnderDomainMaxSpamLevel(): void
    {
        [
            'recipient' => $recipient,
            'spamAccepted' => $spamAccepted,
            'spamRejected' => $spamRejected,
        ] = $this->setupMessages();

        $messageRecipients = $this->reportService->getSpamMessageRecipients($recipient);

        self::assertCount(1, $messageRecipients);
        self::assertSame($messageRecipients[0]->getMailId(), $spamRejected->getMailId());
        self::assertNotSame($messageRecipients[0]->getMailId(), $spamAccepted->getMailId());
    }

    public function testGetSpamMessageRecipientAlsoRetrievesListMessageAboveDomainMaxSpamLevel(): void
    {
        [
            'recipient' => $recipient,
            'senderAddress' => $senderAddress,
            'recipientAddress' => $recipientAddress,
        ] = $this->minimalSetup();
        $messageRecipient = $this->addMail(
            $senderAddress,
            $recipientAddress,
            MessageStatus::SPAMMED,
            spamScore: 12,
            isMailingList: true,
        );

        $messageRecipients = $this->reportService->getSpamMessageRecipients($recipient);

        self::assertCount(1, $messageRecipients);
        self::assertSame($messageRecipient->getMailId(), $messageRecipients[0]->getMailId());
    }

    /**
     * Creates a domain, a sender and a recipient users, then configure addresses.
     *
     * @return array{
     *     recipient: User,
     *     senderAddress: Address,
     *     recipientAddress: Address,
     * }
     */
    private function minimalSetup(): array
    {
        $domain = DomainFactory::createOne([
            'reportSpamLevel' => 5,
        ]);
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        return [
            'recipient' => $recipient,
            'senderAddress' => $addrS,
            'recipientAddress' => $addrR,
        ];
    }

    /**
     * Creates a mail with needed DB records (Message, MessageRecipient, Quarantine).
     */
    private function addMail(
        Address $sender,
        Address $recipient,
        int $status,
        float $spamScore,
        ?bool $isMailingList = null,
    ): MessageRecipient {
        $message = $this->setupMail(
            $sender,
            [$recipient],
            status: $status,
            messageAttributes: [
                'isMlist' => $isMailingList,
            ],
            messageRecipientAttributes: [
                'bspamLevel' => $spamScore,
            ],
        );

        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        return $messageRecipient;
    }

    /**
     * Creates a sender and a recipient (User) both associated to a domain.
     * Then, create a Message with its MessageRecipient for each cases:
     *   - standard message
     *   - list message
     *   - spam (under domain max spam level)
     *   - spam (above domain max spam level)
     *
     * @return array{
     *     domain: Domain,
     *     recipient: User,
     *     senderAddress: Address,
     *     recipientAddress: Address,
     *     standard: MessageRecipient,
     *     list: MessageRecipient,
     *     spamAccepted: MessageRecipient,
     *     spamRejected: MessageRecipient
     * }
     */
    private function setupMessages(): array
    {
        $domain = DomainFactory::createOne([
            'reportSpamLevel' => 5,
        ]);
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);

        // Standard message
        $standardMessage = $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::UNTREATED,
            messageAttributes: [
                'isMlist' => false,
            ],
            messageRecipientAttributes: [
                'bspamLevel' => -2,
            ],
        );
        $standard = $standardMessage->getMessageRecipients()->first();
        self::assertNotFalse($standard);

        // Mailing list message
        $listMessage = $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::UNTREATED,
            messageAttributes: [
                'isMlist' => true,
            ],
            messageRecipientAttributes: [
                'bspamLevel' => -2,
            ],
        );
        $list = $listMessage->getMessageRecipients()->first();
        self::assertNotFalse($list);

        // Spam (under domain authorized spam level)
        $spamAcceptedMessage = $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::SPAMMED,
            messageAttributes: [
                'isMlist' => false,
            ],
            messageRecipientAttributes: [
                'bspamLevel' => 2,
            ],
        );
        $spamAccepted = $spamAcceptedMessage->getMessageRecipients()->first();
        self::assertNotFalse($spamAccepted);

        // Spam (above domain authorized spam level)
        $spamRejectedMessage = $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::SPAMMED,
            messageAttributes: [
                'isMlist' => false,
            ],
            messageRecipientAttributes: [
                'bspamLevel' => 12,
            ],
        );
        $spamRejected = $spamRejectedMessage->getMessageRecipients()->first();
        self::assertNotFalse($spamRejected);

        return [
            'domain' => $domain,
            'recipient' => $recipient,
            'senderAddress' => $addrS,
            'recipientAddress' => $addrR,
            'standard' => $standard,
            'list' => $list,
            'spamAccepted' => $spamAccepted,
            'spamRejected' => $spamRejected,
        ];
    }
}
