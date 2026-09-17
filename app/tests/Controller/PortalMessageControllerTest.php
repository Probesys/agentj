<?php

namespace App\Tests\Controller;

use App\Amavis\MessageStatus;
use App\Service\MessageService;
use App\Util\Url;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class PortalMessageControllerTest extends WebTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;
    private UrlGeneratorInterface $urlGenerator;
    private MessageService $messageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $container = static::getContainer();
        $this->urlGenerator = $container->get(UrlGeneratorInterface::class);
        $this->messageService = $container->get(MessageService::class);
    }

    public function testUserCanAuthorizeItsMessageFromReport(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient2);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);
        $token = $this->messageService->getReleaseToken($message, $recipient2);
        $urlAuthorize = $this->urlGenerator->generate('portal_message_authorized', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getRseqnum(),
            'new' => 1,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlAuthorize);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient2->getStatus());
    }

    public function testUserCanAuthorizeItsMessageFromReportOldVersion(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient2);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);
        $token = $this->messageService->getReleaseToken($message, $recipient2);
        $urlAuthorize = $this->urlGenerator->generate('portal_message_authorized', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getAddress()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlAuthorize);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient2->getStatus());
    }

    public function testUserCannotAuthorizeMessageFromOtherUser(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient1);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);
        $token = $this->messageService->getReleaseToken($message, $recipient1);
        $urlAuthorize = $this->urlGenerator->generate('portal_message_authorized', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getRseqnum(),
            'new' => 1,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlAuthorize);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
    }

    public function testUserCannotAuthorizeMessageFromOtherUserOldVersion(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient1);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);
        $token = $this->messageService->getReleaseToken($message, $recipient1);
        $urlAuthorize = $this->urlGenerator->generate('portal_message_authorized', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getAddress()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlAuthorize);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
    }

    public function testUserCanRestoreMessageFromReport(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient2);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);
        $token = $this->messageService->getReleaseToken($message, $recipient2);
        $urlRestore = $this->urlGenerator->generate('portal_message_restore', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getRseqnum(),
            'new' => 1,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlRestore);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient2->getStatus());
    }

    public function testUserCanRestoreMessageFromReportOldVersion(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient2);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);
        $token = $this->messageService->getReleaseToken($message, $recipient2);
        $urlRestore = $this->urlGenerator->generate('portal_message_restore', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getAddress()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlRestore);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient2->getStatus());
    }

    public function testUserCannotRestoreMessageFromOtherUser(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient1);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);
        $token = $this->messageService->getReleaseToken($message, $recipient1);
        $urlRestore = $this->urlGenerator->generate('portal_message_restore', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getRseqnum(),
            'new' => 1,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlRestore);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
    }

    public function testUserCannotRestoreMessageFromOtherUserOldVersion(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient1);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);
        $token = $this->messageService->getReleaseToken($message, $recipient1);
        $urlRestore = $this->urlGenerator->generate('portal_message_restore', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getAddress()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlRestore);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
    }
}
