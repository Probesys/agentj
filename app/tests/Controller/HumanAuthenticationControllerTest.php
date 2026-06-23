<?php

namespace App\Tests\Controller;

use AltchaOrg\Altcha\Solution;
use App\Amavis\MessageStatus;
use App\Service\AltchaService;
use App\Service\HumanAuthenticationService;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class HumanAuthenticationControllerTest extends WebTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;
    private AltchaService $altchaService;
    private HumanAuthenticationService $humanAuthenticationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $container = static::getContainer();
        $this->altchaService = $container->get(AltchaService::class);
        $this->humanAuthenticationService = $container->get(HumanAuthenticationService::class);
    }

    public function testHumanAuthenticationSucceedsWithValidAltchaTokenAndCsrfToken(): void
    {
        // Create a message
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR]);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setStatus(MessageStatus::UNTREATED);
        $this->save($messageRecipient);
        // Create token to release this message
        $token = $this->humanAuthenticationService->encryptToken($message, $domain);
        // Create a valid altcha challenge solution
        $altchaSolution = $this->altchaPayload();

        $url = "/check/" . $token;
        $csrfToken = $this->generateCsrfToken($this->client, 'human_auth');
        $this->client->request(Request::METHOD_POST, $url, [
            'human_authentication' => [
                'email' => $sender->getEmail(),
                'altcha' => $altchaSolution,
                '_token' => $csrfToken,
            ],
        ]);

        self::assertResponseIsSuccessful();
        $this->refresh($message);
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient->getStatus());
    }

    public function testHumanAuthenticationFailsIfAltchaTokenIsInvalid(): void
    {
        // Create a message
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR]);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setStatus(MessageStatus::UNTREATED);
        $this->save($messageRecipient);
        // Create token to release this message
        $token = $this->humanAuthenticationService->encryptToken($message, $domain);
        // Create an invalid altcha challenge solution
        $altchaSolution = $this->altchaPayload(valid: false);

        $url = "/check/" . $token;
        $csrfToken = $this->generateCsrfToken($this->client, 'human_auth');
        $this->client->request(Request::METHOD_POST, $url, [
            'human_authentication' => [
                'email' => $sender->getEmail(),
                'altcha' => $altchaSolution,
                '_token' => $csrfToken,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->refresh($message);
        $this->refresh($messageRecipient);
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient->getStatus());
    }

    public function testHumanAuthenticationFailsIfCsrfTokenIsInvalid(): void
    {
        // Create a message
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR]);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setStatus(MessageStatus::UNTREATED);
        $this->save($messageRecipient);
        // Create token to release this message
        $token = $this->humanAuthenticationService->encryptToken($message, $domain);
        // Create a valid altcha challenge solution
        $altchaSolution = $this->altchaPayload();

        $url = "/check/" . $token;
        $this->client->request(Request::METHOD_POST, $url, [
            'human_authentication' => [
                'email' => $sender->getEmail(),
                'altcha' => $altchaSolution,
                '_token' => 'invalidCsrfToken',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->refresh($message);
        $this->refresh($messageRecipient);
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient->getStatus());
    }

    private function altchaPayload(bool $valid = true): string
    {
        $challenge = $this->altchaService->buildChallenge(cost: 1);

        if ($valid) {
            $solution = $this->altchaService->solveChallenge($challenge);
        } else {
            $solution = new Solution(0, '');
        }

        $payload = $this->altchaService->buildPayload($challenge, $solution);

        return $payload->toBase64();
    }
}
