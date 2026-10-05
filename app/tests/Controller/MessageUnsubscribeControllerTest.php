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
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class MessageUnsubscribeControllerTest extends WebTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testGetUnsubscribeRedirectsToTheListWebsiteForHttpsMethod(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_GET, $this->unsubscribeUrl($messageRecipient));

        self::assertResponseRedirects('https://lists.example.com/unsubscribe/abc');
        $request = $this->getUnsubscribeRequestRepository()->findLastForMessageRecipient($messageRecipient);
        self::assertNotNull($request);
        self::assertSame(UnsubscribeMethods::METHOD_HTTPS, $request->getMethod());
        self::assertSame(UnsubscribeRequest::STATUS_INITIATED, $request->getStatus());
    }

    public function testGetUnsubscribeRecordsTheImpersonatingAdminAsRequester(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $domain = $user->getDomain();
        self::assertNotNull($domain);
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        $this->client->request(Request::METHOD_GET, '/', ['_switch_user' => $user->getUsername()]);

        $this->client->request(Request::METHOD_GET, $this->unsubscribeUrl($messageRecipient));

        self::assertResponseRedirects('https://lists.example.com/unsubscribe/abc');
        $request = $this->getUnsubscribeRequestRepository()->findLastForMessageRecipient($messageRecipient);
        self::assertNotNull($request);
        self::assertSame($admin->getId(), $request->getRequestedBy()?->getId());
    }

    public function testGetUnsubscribeFailsIfCsrfTokenIsInvalid(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_GET, $this->unsubscribeUrl($messageRecipient, 'invalid'));

        self::assertResponseStatusCodeSame(403);
        self::assertSame(0, $this->getUnsubscribeRequestRepository()->count([]));
    }

    public function testGetUnsubscribeFailsIfUserIsNotTheRecipient(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $otherUser = UserFactory::new()->user($user->getDomain())->create();
        $this->client->loginUser($otherUser);

        $this->client->request(Request::METHOD_GET, $this->unsubscribeUrl($messageRecipient));

        self::assertResponseStatusCodeSame(403);
        self::assertSame(0, $this->getUnsubscribeRequestRepository()->count([]));
    }

    public function testGetIndexDisplaysUnsubscribeActionForListMessages(): void
    {
        [$user] = $this->setupListMessage();
        $this->client->loginUser($user);

        $crawler = $this->client->request(Request::METHOD_GET, '/message');

        self::assertResponseIsSuccessful();
        $action = $crawler->filter('a[href*="/unsubscribe?"]');
        self::assertCount(1, $action);
        // The HTTPS method redirects to the list website: opened in a new tab
        self::assertSame('true', $action->attr('data-confirm-target-blank'));
        // The dialog displays the page where the user is redirected
        self::assertStringContainsString(
            'You will be redirected to the page:<br>'
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
        self::assertCount(0, $crawler->filter('a[href*="/unsubscribe?"]'));
    }

    public function testGetIndexDisplaysLastUnsubscribeRequest(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $this->client->loginUser($user);
        $this->client->request(Request::METHOD_GET, $this->unsubscribeUrl($messageRecipient));

        $crawler = $this->client->request(Request::METHOD_GET, '/message');

        self::assertResponseIsSuccessful();
        $action = $crawler->filter('a[href*="/unsubscribe?"]');
        self::assertCount(1, $action);
        self::assertStringContainsString('Unsubscription initiated on', $action->text());
    }

    public function testGetShowDisplaysListInformation(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_GET, $this->showUrl($messageRecipient));

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('news.example.com', $content);
        self::assertStringContainsString('Web page', $content);
        self::assertStringContainsString('https://lists.example.com/unsubscribe/abc', $content);
        self::assertStringNotContainsString('Last unsubscription request', $content);
    }

    public function testGetShowDisplaysLastUnsubscribeRequest(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $this->client->loginUser($user);
        $this->client->request(Request::METHOD_GET, $this->unsubscribeUrl($messageRecipient));

        $this->client->request(Request::METHOD_GET, $this->showUrl($messageRecipient));

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Last unsubscription request', $content);
        self::assertStringContainsString('Unsubscription initiated on', $content);
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
        self::assertStringContainsString('Not provided', $content);
        self::assertStringContainsString('Not available', $content);
    }

    private function showUrl(MessageRecipient $messageRecipient): string
    {
        return "/message/0/{$messageRecipient->getMailId()}/{$messageRecipient->getRseqnum()}/show/";
    }

    private function unsubscribeUrl(MessageRecipient $messageRecipient, ?string $csrfToken = null): string
    {
        $mailId = $messageRecipient->getMailId();
        $rseqnum = $messageRecipient->getRseqnum();
        $csrfToken ??= $this->generateCsrfToken($this->client, "unsubscribe-{$mailId}-{$rseqnum}");

        return "/message/0/{$mailId}/{$rseqnum}/unsubscribe?_token={$csrfToken}";
    }

    private function getUnsubscribeRequestRepository(): UnsubscribeRequestRepository
    {
        return static::getContainer()->get(UnsubscribeRequestRepository::class);
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
