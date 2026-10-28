<?php

namespace App\Tests\Controller;

use App\Amavis\MessageStatus;
use App\Entity\MessageRecipient;
use App\Entity\UnsubscribeRequest;
use App\Entity\User;
use App\Model\UnsubscribeMethods;
use App\Repository\UnsubscribeRequestRepository;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageListMetadataFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class MessageUnsubscribeControllerTest extends WebTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;
    private UnsubscribeRequestRepository $unsubscribeRequestRepository;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->unsubscribeRequestRepository = static::getContainer()->get(UnsubscribeRequestRepository::class);
        $this->translator = static::getContainer()->get(TranslatorInterface::class);
    }

    public function testPostUnsubscribeInitiatesTheRequestForHttpsMethod(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $this->client->loginUser($user);

        $this->postUnsubscribe($messageRecipient);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains(
            '.flash-success',
            $this->translator->trans('Message.Flash.unsubscribeInitiated', locale: 'en'),
        );
        $request = $this->unsubscribeRequestRepository->findLastForMessageRecipient($messageRecipient);
        self::assertNotNull($request);
        self::assertSame(UnsubscribeMethods::METHOD_HTTPS, $request->getMethod());
        self::assertSame(UnsubscribeRequest::STATUS_INITIATED, $request->getStatus());
    }

    public function testPostUnsubscribeFailsIfCsrfTokenIsInvalid(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $this->client->loginUser($user);

        $this->postUnsubscribe($messageRecipient, 'invalid');

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains(
            '.flash-error',
            $this->translator->trans('Generics.flash.invalidCsrfToken', locale: 'en'),
        );
        self::assertSame(0, $this->unsubscribeRequestRepository->count([]));
    }

    public function testPostUnsubscribeFailsIfUserIsNotTheRecipient(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $otherUser = UserFactory::new()->user($user->getDomain())->create();
        $this->client->loginUser($otherUser);

        $this->postUnsubscribe($messageRecipient);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains(
            '.flash-error',
            $this->translator->trans('Message.Flash.unsubscribeImpossible', locale: 'en'),
        );
        self::assertSame(0, $this->unsubscribeRequestRepository->count([]));
    }

    public function testGetUnsubscribeIsNotAllowed(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $this->client->loginUser($user);

        $mailId = $messageRecipient->getMailId();
        $rseqnum = $messageRecipient->getRseqnum();
        $this->client->request(Request::METHOD_GET, "/message/0/{$mailId}/{$rseqnum}/unsubscribe");

        self::assertResponseStatusCodeSame(405);
        self::assertSame(0, $this->unsubscribeRequestRepository->count([]));
    }

    public function testGetIndexDisplaysUnsubscribeActionForListMessages(): void
    {
        [$user] = $this->setupListMessage();
        $this->client->loginUser($user);

        $crawler = $this->client->request(Request::METHOD_GET, '/message');

        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form[action$="/unsubscribe"]');
        self::assertCount(1, $form);
        self::assertSame('unsubscribe', $form->attr('data-controller'));
        self::assertSame('https://lists.example.com/unsubscribe/abc', $form->attr('data-unsubscribe-url-value'));
        $action = $form->filter('button.confirmModal');
        // The dialog displays the page where the user is redirected
        self::assertStringContainsString(
            $this->translator->trans('Message.Dialogs.confirmUnsubscribeTarget.https', locale: 'en') . '<br>'
                . '<span class="text-break">https://lists.example.com/unsubscribe/abc</span>',
            (string) $action->attr('data-dialog-content'),
        );
    }

    public function testGetIndexHidesUnsubscribeActionForSpam(): void
    {
        [$user] = $this->setupListMessage(status: MessageStatus::SPAMMED);
        $this->client->loginUser($user);

        $crawler = $this->client->request(Request::METHOD_GET, '/message/spam');

        self::assertResponseIsSuccessful();
        // The message is listed, but without the unsubscribe action
        self::assertCount(1, $crawler->filter('td[data-title="Subject"]'));
        self::assertCount(0, $crawler->filter('form[action$="/unsubscribe"]'));
    }

    public function testGetIndexDisplaysLastUnsubscribeRequest(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $this->client->loginUser($user);
        $this->postUnsubscribe($messageRecipient);

        $crawler = $this->client->request(Request::METHOD_GET, '/message');

        self::assertResponseIsSuccessful();
        $action = $crawler->filter('form[action$="/unsubscribe"] button');
        self::assertCount(1, $action);
        // Without the date, as only the text is checked
        $status = $this->translator->trans('Message.Unsubscribe.status.initiated', ['date' => ''], locale: 'en');
        self::assertStringContainsString(trim($status), $action->text());
    }

    public function testGetShowDisplaysListInformation(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_GET, $this->showUrl($messageRecipient));

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('news.example.com', $content);
        self::assertStringContainsString(
            $this->translator->trans('Entities.Message.unsubscribeMethods.https', locale: 'en'),
            $content,
        );
        self::assertStringContainsString('https://lists.example.com/unsubscribe/abc', $content);
        self::assertStringNotContainsString(
            $this->translator->trans('Entities.Message.lastUnsubscribeRequest', locale: 'en'),
            $content,
        );
    }

    public function testGetShowDisplaysLastUnsubscribeRequest(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $this->client->loginUser($user);
        $this->postUnsubscribe($messageRecipient);

        $this->client->request(Request::METHOD_GET, $this->showUrl($messageRecipient));

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString(
            $this->translator->trans('Entities.Message.lastUnsubscribeRequest', locale: 'en'),
            $content,
        );
        $status = $this->translator->trans('Message.Unsubscribe.status.initiated', ['date' => ''], locale: 'en');
        self::assertStringContainsString(trim($status), $content);
    }

    public function testGetShowHandlesListMessageWithoutMetadata(): void
    {
        // Amavis does not save list metadata when the message has neither a
        // List-Id nor a List-Unsubscribe header (or for legacy messages)
        [$user, $messageRecipient] = $this->setupListMessage(withMetadata: false);
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_GET, $this->showUrl($messageRecipient));

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString(
            $this->translator->trans('Entities.Message.listIdUnknown', locale: 'en'),
            $content,
        );
        self::assertStringContainsString(
            $this->translator->trans('Entities.Message.unsubscribeMethods.none', locale: 'en'),
            $content,
        );
    }

    private function showUrl(MessageRecipient $messageRecipient): string
    {
        return "/message/0/{$messageRecipient->getMailId()}/{$messageRecipient->getRseqnum()}/show/";
    }

    private function postUnsubscribe(MessageRecipient $messageRecipient, ?string $csrfToken = null): void
    {
        $mailId = $messageRecipient->getMailId();
        $rseqnum = $messageRecipient->getRseqnum();
        $csrfToken ??= $this->generateCsrfToken($this->client, 'message_unsubscribe');

        $this->client->request(Request::METHOD_POST, "/message/0/{$mailId}/{$rseqnum}/unsubscribe", [
            '_token' => $csrfToken,
        ]);
    }

    /**
     * Create an untreated mailing list message sent to a new user, with its
     * list metadata (as Amavis would save them) unless $withMetadata is false.
     *
     * @return array{User, MessageRecipient}
     */
    private function setupListMessage(int $status = MessageStatus::UNTREATED, bool $withMetadata = true): array
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: $status, messageAttributes: [
            'isMlist' => true,
        ]);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        if ($withMetadata) {
            MessageListMetadataFactory::new()->forMessage($message)->create([
                'listId' => 'news.example.com',
                'listUnsubscribe' => '<https://lists.example.com/unsubscribe/abc>',
            ]);
        }

        return [$recipient, $messageRecipient];
    }
}
