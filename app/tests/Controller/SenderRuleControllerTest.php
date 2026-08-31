<?php

namespace App\Tests\Controller;

use App\Amavis\MessageStatus;
use App\Service\MessageService;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageRecipientFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use Override;
use App\Util\Url;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SenderRuleControllerTest extends WebTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;
    private MessageService $messageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->messageService = $this->client->getContainer()->get(MessageService::class);
    }

    public function testSenderRulesAreNotCaseSensitive(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($otherDomain)->create([
            'email' => 'addresscaseTest@' . $otherDomain,
        ]);
        $otherSender = UserFactory::new()->user($domain)->create([
            'email' => 'addressCaseTest@' . $otherDomain,
        ]);
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $messageRecipient = MessageRecipientFactory::findBy(['mailId' => $mailId])[0];
        $addrOS = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($otherSender->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $otherSender->getEmail(),
        ]);
        $this->setupMail($addrOS, $addrR, status: MessageStatus::UNTREATED);

        // Authorize sender for domain
        $this->messageService->authorizeSenderForDomain($messageRecipient, 0);

        // List authorized messages
        $crawler = $this->client->request(Request::METHOD_GET, '/message/authorized');
        // It should contains all messages not depending on case
        $titles = $crawler
            ->filter('td[data-title="Sender"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertContains($addrS->getEmail(), $titles);
        self::assertContains($addrOS->getEmail(), $titles);
    }
}
