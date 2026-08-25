<?php

namespace App\Tests\Controller;

use App\Amavis\MessageStatus;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class MessageControllerTest extends WebTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    public function testHtmlBodyIsSanitized(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $client->loginUser($recipient);
        [$senderAddress, $recipientAddress] = $this->setupAddresses($sender, $recipient);
        $body = <<<'HTML'
            <strong>Security test</strong>
            <p>If this message is printed correcly, your email client is well configured.</p>
            <img src="x" onerror="alert('Demo XSS')">
            <a hrel="https://threat.security.com/exploit">More information here.</a>
            The security team
        HTML;
        $message = $this->setupMail($senderAddress, $recipientAddress, body: $body, status: MessageStatus::AUTHORIZED);
        $url = sprintf(
            "/message/%s/%s/%s/iframe-content",
            $message->getPartitionTag(),
            $message->getMailId(),
            $recipientAddress->getId(),
        );
        $client->request(Request::METHOD_GET, $url);

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('<strong>Security test</strong>', $content);
        self::assertStringNotContainsString('<img', $content);
        self::assertStringContainsString('[IMAGE]', $content);
        self::assertStringNotContainsString('onerror=', $content);
        self::assertStringContainsString('[URL] More information here.', $content);
    }
}
