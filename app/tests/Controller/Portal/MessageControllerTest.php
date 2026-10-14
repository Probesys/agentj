<?php

namespace App\Tests\Controller\Portal;

use App\Amavis\MessageStatus;
use App\Model\LogAction;
use App\Repository\UnsubscribeRequestRepository;
use App\Service\MessageService;
use App\Util\Url;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\LogFactory;
use App\Tests\Factory\MessageListMetadataFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class MessageControllerTest extends WebTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;
    private UrlGeneratorInterface $urlGenerator;
    private MessageService $messageService;
    private TranslatorInterface $translator;
    private UnsubscribeRequestRepository $unsubscribeRequestRepository;

    private const ONE_CLICK_HEADERS = [
        'list-id' => 'news@example.com',
        'list-unsubscribe' => '<https://lists.example.com/unsubscribe/abc>, <mailto:leave@lists.example.com>',
        'list-unsubscribe-post' => 'List-Unsubscribe=One-Click',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $container = static::getContainer();
        $this->urlGenerator = $container->get(UrlGeneratorInterface::class);
        $this->messageService = $container->get(MessageService::class);
        $this->translator = $container->get(TranslatorInterface::class);
        $this->unsubscribeRequestRepository = $container->get(UnsubscribeRequestRepository::class);
    }

    public function testUserCanAuthorizeItsMessageFromReport(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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

        $this->client->request(Request::METHOD_POST, $urlAuthorize);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient2->getStatus());
        LogFactory::assert()->exists([
            'action' => LogAction::Authorized->value,
            'domain' => $domain,
        ]);
    }

    public function testUserHasToConfirmAuthorization(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
        self::assertSelectorTextContains(
            '[data-test=confirm-content]',
            $this->translator->trans(
                'Portal.Messages.confirmAuthorize',
                ['from' => $message->getFromAddr()],
                locale: 'en',
            ),
        );
        self::assertSelectorTextContains(
            '[data-test=confirm-button]',
            $this->translator->trans('Portal.Messages.confirm', locale: 'en'),
        );
    }

    public function testUserCanAuthorizeItsMessageFromReportOldVersion(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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

        $this->client->request(Request::METHOD_POST, $urlAuthorize);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient2->getStatus());
    }

    public function testUserHasToConfirmAuthorizationFromReportOldVersion(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
        self::assertSelectorTextContains(
            '[data-test=confirm-content]',
            $this->translator->trans(
                'Portal.Messages.confirmAuthorize',
                ['from' => $message->getFromAddr()],
                locale: 'en',
            ),
        );
        self::assertSelectorTextContains(
            '[data-test=confirm-button]',
            $this->translator->trans('Portal.Messages.confirm', locale: 'en'),
        );
    }

    public function testUserCannotAuthorizeMessageFromOtherUser(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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

        $this->client->request(Request::METHOD_POST, $urlAuthorize);

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

        $this->client->request(Request::METHOD_POST, $urlAuthorize);

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

        $this->client->request(Request::METHOD_POST, $urlRestore);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient2->getStatus());
        LogFactory::assert()->exists([
            'action' => LogAction::Restore->value,
            'domain' => $domain,
        ]);
    }

    public function testUserHasToConfirmRestore(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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
        $urlAuthorize = $this->urlGenerator->generate('portal_message_restore', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getRseqnum(),
            'new' => 1,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlAuthorize);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
        self::assertSelectorTextContains(
            '[data-test=confirm-content]',
            $this->translator->trans(
                'Portal.Messages.confirmRestore',
                ['from' => $message->getFromAddr(), 'subject' => $message->getSubject()],
                locale: 'en',
            ),
        );
        self::assertSelectorTextContains(
            '[data-test=confirm-button]',
            $this->translator->trans('Portal.Messages.confirm', locale: 'en'),
        );
    }

    public function testUserCanRestoreMessageFromReportOldVersion(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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

        $this->client->request(Request::METHOD_POST, $urlRestore);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient2->getStatus());
    }

    public function testUserHasToConfirmRestoreFromReportOldVersion(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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
        $urlAuthorize = $this->urlGenerator->generate('portal_message_restore', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getAddress()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlAuthorize);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
        self::assertSelectorTextContains(
            '[data-test=confirm-content]',
            $this->translator->trans(
                'Portal.Messages.confirmRestore',
                ['from' => $message->getFromAddr(), 'subject' => $message->getSubject()],
                locale: 'en',
            ),
        );
        self::assertSelectorTextContains(
            '[data-test=confirm-button]',
            $this->translator->trans('Portal.Messages.confirm', locale: 'en'),
        );
    }

    public function testUserCannotRestoreMessageFromOtherUser(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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

    public function testUserCanBanItsMessageFromReport(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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
        $urlBan = $this->urlGenerator->generate('portal_message_banned', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getRseqnum(),
            'new' => 1,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_POST, $urlBan);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::BANNED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::BANNED, $messageRecipient2->getStatus());
        LogFactory::assert()->exists([
            'action' => LogAction::Banned->value,
            'domain' => $domain,
        ]);
    }

    public function testUserHasToConfirmBannishment(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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
        $urlAuthorize = $this->urlGenerator->generate('portal_message_banned', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getRseqnum(),
            'new' => 1,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlAuthorize);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
        self::assertSelectorTextContains(
            '[data-test=confirm-content]',
            $this->translator->trans(
                'Portal.Messages.confirmBan',
                ['from' => $message->getFromAddr()],
                locale: 'en',
            ),
        );
        self::assertSelectorTextContains(
            '[data-test=confirm-button]',
            $this->translator->trans('Portal.Messages.confirm', locale: 'en'),
        );
    }

    public function testUserCanBanItsMessageFromReportOldVersion(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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
        $urlBan = $this->urlGenerator->generate('portal_message_banned', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getAddress()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_POST, $urlBan);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::BANNED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::BANNED, $messageRecipient2->getStatus());
    }

    public function testUserHasToConfirmBannishmentFromReportOldVersion(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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
        $urlAuthorize = $this->urlGenerator->generate('portal_message_banned', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getAddress()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $crawler = $this->client->request(Request::METHOD_GET, $urlAuthorize);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
        self::assertSelectorTextContains(
            '[data-test=confirm-content]',
            $this->translator->trans(
                'Portal.Messages.confirmBan',
                ['from' => $message->getFromAddr()],
                locale: 'en',
            ),
        );
        self::assertSelectorTextContains(
            '[data-test=confirm-button]',
            $this->translator->trans('Portal.Messages.confirm', locale: 'en'),
        );
    }

    public function testUserCannotBanMessageFromOtherUser(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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
        $urlBan = $this->urlGenerator->generate('portal_message_banned', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getRseqnum(),
            'new' => 1,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlBan);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
    }

    public function testUserCannotBanMessageFromOtherUserOldVersion(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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
        $urlBan = $this->urlGenerator->generate('portal_message_banned', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getAddress()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlBan);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
    }

    public function testUserCanUnsubscribeItsMessageFromReport(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail(
            $addrS,
            [$addrR1,$addrR2],
            status: MessageStatus::UNTREATED,
            messageAttributes: [
                'isMlist' => true,
            ],
        );
        MessageListMetadataFactory::new()->forMessage($message)->create([
            'listId' => self::ONE_CLICK_HEADERS['list-id'],
            'listUnsubscribe' => self::ONE_CLICK_HEADERS['list-unsubscribe'],
            'listUnsubscribePost' => self::ONE_CLICK_HEADERS['list-unsubscribe-post'],
        ]);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);
        $token = $this->messageService->getReleaseToken($message, $recipient2);
        $urlUnsubscribe = $this->urlGenerator->generate('portal_message_unsubscribe', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getRseqnum(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_POST, $urlUnsubscribe);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
        // Status not changed, but check unsubscribe requests
        self::assertNull($this->unsubscribeRequestRepository->findLastForMessageRecipient($messageRecipient1));
        self::assertNotNull($this->unsubscribeRequestRepository->findLastForMessageRecipient($messageRecipient2));
    }

    public function testUserHasToConfirmUnsubscribe(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail(
            $addrS,
            [$addrR1,$addrR2],
            status: MessageStatus::UNTREATED,
            messageAttributes: [
                'isMlist' => true,
            ],
        );
        MessageListMetadataFactory::new()->forMessage($message)->create([
            'listId' => self::ONE_CLICK_HEADERS['list-id'],
            'listUnsubscribe' => self::ONE_CLICK_HEADERS['list-unsubscribe'],
            'listUnsubscribePost' => self::ONE_CLICK_HEADERS['list-unsubscribe-post'],
        ]);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);
        $token = $this->messageService->getReleaseToken($message, $recipient2);
        $urlUnsubscribe = $this->urlGenerator->generate('portal_message_unsubscribe', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getRseqnum(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlUnsubscribe);

        self::assertResponseIsSuccessful();
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
        self::assertSelectorTextContains(
            '[data-test=confirm-content]',
            $this->translator->trans(
                'Portal.Messages.confirmUnsubscribe',
                ['from' => $message->getFromAddr()],
                locale: 'en',
            ),
        );
        self::assertSelectorTextContains(
            '[data-test=confirm-button]',
            $this->translator->trans('Portal.Messages.confirm', locale: 'en'),
        );
    }

    public function testUserCannotUnsubscribeMessageFromOtherUser(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail(
            $addrS,
            [$addrR1,$addrR2],
            status: MessageStatus::UNTREATED,
            messageAttributes: [
                'isMlist' => true,
            ],
        );
        MessageListMetadataFactory::new()->forMessage($message)->create([
            'listId' => self::ONE_CLICK_HEADERS['list-id'],
            'listUnsubscribe' => self::ONE_CLICK_HEADERS['list-unsubscribe'],
            'listUnsubscribePost' => self::ONE_CLICK_HEADERS['list-unsubscribe-post'],
        ]);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);
        $token = $this->messageService->getReleaseToken($message, $recipient1);
        $urlUnsubscribe = $this->urlGenerator->generate('portal_message_unsubscribe', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getRseqnum(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_GET, $urlUnsubscribe);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
        // Status not changed, but check unsubscribe requests
        self::assertNull($this->unsubscribeRequestRepository->findLastForMessageRecipient($messageRecipient1));
        self::assertNull($this->unsubscribeRequestRepository->findLastForMessageRecipient($messageRecipient2));
    }

    public function testUserCannotUnsubscribeMessageWithoutListMetadata(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail(
            $addrS,
            [$addrR1,$addrR2],
            status: MessageStatus::UNTREATED,
            messageAttributes: [
                'isMlist' => true,
            ],
        );
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);
        $token = $this->messageService->getReleaseToken($message, $recipient2);
        $urlUnsubscribe = $this->urlGenerator->generate('portal_message_unsubscribe', [
            'token' => $token,
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
            'rseqnum' => $messageRecipient2->getRseqnum(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->client->request(Request::METHOD_POST, $urlUnsubscribe);

        self::assertResponseStatusCodeSame(404);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
        self::assertNull($this->unsubscribeRequestRepository->findLastForMessageRecipient($messageRecipient1));
        self::assertNull($this->unsubscribeRequestRepository->findLastForMessageRecipient($messageRecipient2));
    }
}
