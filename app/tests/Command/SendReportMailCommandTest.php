<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Entity\Address;
use App\Tests\CommandHelper;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageListMetadataFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use App\Util\Url;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SendReportMailCommandTest extends KernelTestCase
{
    use CommandHelper;
    use FactoryHelper;
    use Factories;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private const ONE_CLICK_HEADERS = [
        'list-id' => 'news.example.com',
        'list-unsubscribe' => '<https://lists.example.com/unsubscribe/abc>, <mailto:leave@lists.example.com>',
        'list-unsubscribe-post' => 'List-Unsubscribe=One-Click',
    ];

    public function testReportMailIsSentOnlyToUsersThatHadActivatedReports(): void
    {
        [$addrSender, $addrRecipient1] = $this->setupTest();
        $recipient2 = UserFactory::new()->user()->create([
            'report' => false,
        ]);
        $addrRecipient2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $this->setupMail(
            $addrSender,
            [$addrRecipient1, $addrRecipient2],
            status: MessageStatus::UNTREATED,
        );

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        // Only 1 report email is sent to recipient1 that activated reports
        self::assertEmailCount(1);
        $message = self::getMailerMessage();
        self::assertInstanceOf(Message::class, $message);
        self::assertEmailAddressContains($message, 'To', $addrRecipient1->getEmail());
        $mail = MessageConverter::toEmail($message);
        $this->assertOccurencesCount($mail, 'all', $addrSender->getEmail(), 1);
        $this->assertOccurencesCount($mail, 'standard', $addrSender->getEmail(), 1);
        $this->assertOccurencesCount($mail, 'list', $addrSender->getEmail(), 0);
        $this->assertOccurencesCount($mail, 'spam', $addrSender->getEmail(), 0);
    }

    public function testReportMailIsNotSentIfNoMessageAtAll(): void
    {
        $this->setupTest();

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testReportMailIsNotSentToAliasesOfUserThatActivatedReport(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->alias($recipient1)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $this->setupMail($addrS, [$addrR2], status: MessageStatus::UNTREATED);

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        // Only 1 mail sent to recipient1.
        // Recipient2 is an alias, so report is not sent to him.
        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertEmailAddressContains($mail, 'To', $addrR->getEmail());
    }

    public function testReportMailIsSentAndTagsAreReplaced(): void
    {
        [$addrS, $addrR] = $this->setupTest(reportType: 'full');
        $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::UNTREATED,
            messageAttributes: [
                'isMlist' => true,
            ],
        );

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertEmailAddressContains($mail, 'To', $addrR->getEmail());
        self::assertEmailTextBodyNotContains($mail, '[FOREACH_MESSAGE_STANDARD]');
        self::assertEmailTextBodyNotContains($mail, '[ENDFOREACH_MESSAGE_STANDARD]');
        self::assertEmailTextBodyNotContains($mail, '[IF_MESSAGES_STANDARD]');
        self::assertEmailTextBodyNotContains($mail, '[ENDIF_MESSAGES_STANDARD]');
        self::assertEmailTextBodyNotContains($mail, '[FOREACH_MESSAGE_LIST]');
        self::assertEmailTextBodyNotContains($mail, '[ENDFOREACH_MESSAGE_LIST]');
        self::assertEmailTextBodyNotContains($mail, '[IF_MESSAGES_LIST]');
        self::assertEmailTextBodyNotContains($mail, '[ENDIF_MESSAGES_LIST]');
        self::assertEmailTextBodyNotContains($mail, '[FOREACH_MESSAGE_SPAM]');
        self::assertEmailTextBodyNotContains($mail, '[ENDFOREACH_MESSAGE_SPAM]');
        self::assertEmailTextBodyNotContains($mail, '[IF_MESSAGES_SPAM]');
        self::assertEmailTextBodyNotContains($mail, '[ENDIF_MESSAGES_SPAM]');
        self::assertEmailTextBodyNotContains($mail, '[MESSAGE_SENDER]');
        self::assertEmailTextBodyNotContains($mail, '[MESSAGE_SUBJECT]');
        self::assertEmailTextBodyNotContains($mail, '[URL_MESSAGE_AUTHORIZE_SENDER]');
        self::assertEmailTextBodyNotContains($mail, '[URL_MESSAGE_RESTORE]');
        self::assertEmailTextBodyNotContains($mail, '[URL_MESSAGE_BAN]');
        self::assertEmailTextBodyNotContains($mail, '[URL_MESSAGE_UNSUBSCRIBE]');
        self::assertEmailTextBodyNotContains($mail, '[URL_MSGS]');
    }

    public function testReportMailShouldNotDisplayUnsubscribeButtonForNotMailingListMessages(): void
    {
        [$addrS, $addrR] = $this->setupTest();
        $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::UNTREATED,
        );

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertEmailAddressContains($mail, 'To', $addrR->getEmail());
        // There should not be a link in the report to unsubscribe
        self::assertEmailTextBodyNotContains($mail, '/unsubscribe');
    }

    public function testReportMailIsSentAndStandardMessageIsDisplayedInAllAndStandardCategories(): void
    {
        [$addrS, $addrR] = $this->setupTest();
        $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::UNTREATED,
        );

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        $message = self::getMailerMessage();
        self::assertInstanceOf(Message::class, $message);
        self::assertEmailAddressContains($message, 'To', $addrR->getEmail());
        $mail = MessageConverter::toEmail($message);
        $this->assertOccurencesCount($mail, 'all', $addrS->getEmail(), 1);
        $this->assertOccurencesCount($mail, 'standard', $addrS->getEmail(), 1);
        $this->assertOccurencesCount($mail, 'list', $addrS->getEmail(), 0);
        $this->assertOccurencesCount($mail, 'spam', $addrS->getEmail(), 0);
    }

    public function testReportMailIsSentAndListMessageIsDisplayedInAllAndListCategories(): void
    {
        [$addrS, $addrR] = $this->setupTest();
        $message = $this->setupMail(
            $addrS,
            [$addrR],
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

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        $message = self::getMailerMessage();
        self::assertEmailAddressContains($message, 'To', $addrR->getEmail());
        self::assertInstanceOf(Message::class, $message);
        $mail = MessageConverter::toEmail($message);
        $this->assertOccurencesCount($mail, 'all', $addrS->getEmail(), 1);
        $this->assertOccurencesCount($mail, 'standard', $addrS->getEmail(), 0);
        $this->assertOccurencesCount($mail, 'list', $addrS->getEmail(), 1);
        $this->assertOccurencesCount($mail, 'spam', $addrS->getEmail(), 0);
        // There should be a link in the report to unsubscribe
        self::assertEmailHtmlBodyContains($mail, '/unsubscribe');
    }

    public function testReportMailIsSentAndSpamMessageIsDisplayedInAllAndSpamCategories(): void
    {
        [$addrS, $addrR] = $this->setupTest();
        $message = $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::SPAMMED,
            messageAttributes: [
                'isMlist' => true,
            ],
            messageRecipientAttributes: [
                'bspamLevel' => 2,
            ],
        );
        MessageListMetadataFactory::new()->forMessage($message)->create([
            'listId' => self::ONE_CLICK_HEADERS['list-id'],
            'listUnsubscribe' => self::ONE_CLICK_HEADERS['list-unsubscribe'],
            'listUnsubscribePost' => self::ONE_CLICK_HEADERS['list-unsubscribe-post'],
        ]);

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        $message = self::getMailerMessage();
        self::assertInstanceOf(Message::class, $message);
        $mail = MessageConverter::toEmail($message);
        $this->assertOccurencesCount($mail, 'all', $addrS->getEmail(), 1);
        $this->assertOccurencesCount($mail, 'standard', $addrS->getEmail(), 0);
        $this->assertOccurencesCount($mail, 'list', $addrS->getEmail(), 0);
        $this->assertOccurencesCount($mail, 'spam', $addrS->getEmail(), 1);
    }

    /**
     * Returns an array with sender Address, then recipient Address.
     *
     * @param 'simple'|'full' $reportType
     *
     * @return array<int, Address>
     */
    private function setupTest(string $reportType = 'simple'): array
    {
        $domain = DomainFactory::new()->withReport($reportType)->create([
            'level' => 1,
            'reportSpamLevel' => 5,
        ]);
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        return $this->setupAddresses($sender, $recipient);
    }

    /**
     * @param 'all'|'standard'|'list'|'spam' $category
     */
    private function assertOccurencesCount(Email $email, string $category, string $address, int $count): void
    {
        $htmlBody = $email->getHtmlBody();

        self::assertTrue(is_string($htmlBody));

        $crawler = new Crawler($htmlBody);

        $filter = $crawler->filter('[data-test="' . $category . '"]');

        if ($filter->count() === 0) {
            self::assertSame($count, 0);
            return;
        }

        $occurences =  substr_count($filter->text(), $address);
        self::assertSame($count, $occurences);
    }
}
