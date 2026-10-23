<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Entity\Address;
use App\Entity\MessageRecipient;
use App\Tests\CommandHelper;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageRecipientFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Mime\Email;
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

    public function testReportMailIsNotSentIfNoMessageAtAll(): void
    {
        $this->minimalSetup();

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testReportMailIsSentAndForeachTagsNotDisplayedIfNoMessagesInGroups(): void
    {
        [$addrS, $addrR] = $this->minimalSetup();
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
        self::assertEmailTextBodyNotContains($mail, '[FOREACH_MESSAGE_STANDARD]');
        self::assertEmailTextBodyNotContains($mail, '[ENDFOREACH_MESSAGE_STANDARD]');
        self::assertEmailTextBodyNotContains($mail, '[FOREACH_MESSAGE_LIST]');
        self::assertEmailTextBodyNotContains($mail, '[ENDFOREACH_MESSAGE_LIST]');
        self::assertEmailTextBodyNotContains($mail, '[FOREACH_MESSAGE_SPAM]');
        self::assertEmailTextBodyNotContains($mail, '[ENDFOREACH_MESSAGE_SPAM]');
    }

    public function testReportMailIsSentAndMessageIsDisplayInCorrectCategories(): void
    {
        [$addrS, $addrR] = $this->minimalSetup();
        $message = $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::UNTREATED,
        );

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        $message = self::getMailerMessage();
        self::assertEmailAddressContains($message, 'To', $addrR->getEmail());
        self::assertEmailHtmlBodyNotContains($message, '[FOREACH_MESSAGE_STANDARD]');
        self::assertEmailHtmlBodyNotContains($message, '[FOREACH_MESSAGE_STANDARD]');
        self::assertEmailHtmlBodyNotContains($message, '[ENDFOREACH_MESSAGE_STANDARD]');
        self::assertEmailHtmlBodyNotContains($message, '[FOREACH_MESSAGE_LIST]');
        self::assertEmailHtmlBodyNotContains($message, '[ENDFOREACH_MESSAGE_LIST]');
        self::assertEmailHtmlBodyNotContains($message, '[FOREACH_MESSAGE_SPAM]');
        self::assertEmailHtmlBodyNotContains($message, '[ENDFOREACH_MESSAGE_SPAM]');
    }

    public function testReportMailIsSentAndStandardMessageIsDisplayInAllAndStandardCategories(): void
    {
        [$addrS, $addrR] = $this->minimalSetup();
        $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::UNTREATED,
        );

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        $message = self::getMailerMessage();
        self::assertNotNull($message);
        self::assertEmailAddressContains($message, 'To', $addrR->getEmail());
        $mail = MessageConverter::toEmail($message);
        $this->assertOccurencesCount($mail, 'all', $addrS->getEmail(), 1);
        $this->assertOccurencesCount($mail, 'standard', $addrS->getEmail(), 1);
        $this->assertOccurencesCount($mail, 'list', $addrS->getEmail(), 0);
        $this->assertOccurencesCount($mail, 'spam', $addrS->getEmail(), 0);
    }

    public function testReportMailIsSentAndListMessageIsDisplayInAllAndListCategories(): void
    {
        [$addrS, $addrR] = $this->minimalSetup();
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
        $message = self::getMailerMessage();
        self::assertNotNull($message);
        self::assertEmailAddressContains($message, 'To', $addrR->getEmail());
        $mail = MessageConverter::toEmail($message);
        $this->assertOccurencesCount($mail, 'all', $addrS->getEmail(), 1);
        $this->assertOccurencesCount($mail, 'standard', $addrS->getEmail(), 0);
        $this->assertOccurencesCount($mail, 'list', $addrS->getEmail(), 1);
        $this->assertOccurencesCount($mail, 'spam', $addrS->getEmail(), 0);
    }

    public function testReportMailIsSentAndSpamMessageIsDisplayInAllAndSpamCategories(): void
    {
        [$addrS, $addrR] = $this->minimalSetup();
        $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::SPAMMED,
            messageRecipientAttributes: [
                'bspamLevel' => 1,
            ],
        );

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        $message = self::getMailerMessage();
        self::assertNotNull($message);
        self::assertEmailAddressContains($message, 'To', $addrR->getEmail());
        $mail = MessageConverter::toEmail($message);
        $this->assertOccurencesCount($mail, 'all', $addrS->getEmail(), 1);
        $this->assertOccurencesCount($mail, 'standard', $addrS->getEmail(), 0);
        $this->assertOccurencesCount($mail, 'list', $addrS->getEmail(), 0);
        $this->assertOccurencesCount($mail, 'spam', $addrS->getEmail(), 1);
    }

    /**
     * Returns an array with sender Address, then recipient Address.
     *
     * @return array<int, Address>
     */
    private function minimalSetup(): array
    {
        $domain = DomainFactory::createOne([
            // Message template containing all new variables:
            //   * LIST_MESSAGES_STANDARD
            //   * FOREACH_MESSAGE_STANDARD
            //   * ENDFOREACH_MESSAGE_STANDARD
            //   * LIST_MESSAGES_LIST
            //   * FOREACH_MESSAGE_LIST
            //   * ENDFOREACH_MESSAGE_LIST
            //   * LIST_MESSAGES_SPAM
            //   * FOREACH_MESSAGE_SPAM
            //   * ENDFOREACH_MESSAGE_SPAM
            // phpcs:ignore Generic.Files.LineLength
            'messageAlert' => '<table class="table layout-table main-template" style="background-color:#E5E5E5;" role="presentation" align="center" bgcolor="#f9fafc" cellpadding="0" cellspacing="0" width="100%"><tbody><tr style="display:none !important;font-size:1px;"><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td align="center" valign="top"><table class="table layout-table templateContainer" style="background-color:#fff;max-width:590px!important;width:590px;" role="presentation" cellpadding="7" cellspacing="0" width="100%"><tbody><tr><td align="center" valign="top"><table class="table layout-table rnb-col-1" role="presentation" align="top" cellpadding="0" cellspacing="0" width="100%"><tbody><tr><td align="center"><a href="https://www.agentj.io" title="Solution antispam AgentJ"><img class="image_resized" style="width:300px;" src="https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png" alt="Solution antispam AgentJ" title="Solution antispam AgentJ"></a></td></tr><tr><td style="color:#3c4858;font-size:14px;"><h3 style="color:#e8576b;font-size:18px;margin-bottom:0px;text-align:center;text-transform:uppercase;"><strong>RAPPORT AGENTJ</strong></h3></td></tr><tr><td style="font-size:1px;line-height:0px;mso-hide:all;" height="20">&nbsp;</td></tr><tr><td style="color:#3c4858;font-size:14px;"><p>Bonjour <strong>[USERNAME]</strong>,</p><p>Ci dessous la liste des mails non traités, en attente sur AgentJ</p><p>[LIST_MAIL_MSGS]</p><p><br>[FOREACH_MESSAGE]</p><p>[MESSAGE_SENDER]: [MESSAGE_SUBJECT]</p><p><a href="[URL_MESSAGE_AUTHORIZE_SENDER]">[URL_MESSAGE_AUTHORIZE_SENDER]</a></p><p>[ENDFOREACH_MESSAGE]</p><p>___</p><p>[LIST_MESSAGES_STANDARD]</p><p>&nbsp;</p><p>[FOREACH_MESSAGE_STANDARD]</p><p>[MESSAGE_SENDER]: [MESSAGE_SUBJECT]</p><p><a href="[URL_MESSAGE_AUTHORIZE_SENDER]">[URL_MESSAGE_AUTHORIZE_SENDER]</a></p><p>[ENDFOREACH_MESSAGE_STANDARD]<br>&nbsp;</p><p>___</p><p>[LIST_MESSAGES_LIST]</p><p>&nbsp;</p><p>[FOREACH_MESSAGE_LIST]</p><p>[MESSAGE_SENDER]: [MESSAGE_SUBJECT]</p><p><a href="[URL_MESSAGE_AUTHORIZE_SENDER]">[URL_MESSAGE_AUTHORIZE_SENDER]</a></p><p>[ENDFOREACH_MESSAGE_LIST]</p><p>&nbsp;</p><p>___</p><p>[LIST_MESSAGES_SPAM]</p><p>&nbsp;</p><p>[FOREACH_MESSAGE_SPAM]</p><p>[MESSAGE_SENDER]: [MESSAGE_SUBJECT]</p><p><a href="[URL_MESSAGE_AUTHORIZE_SENDER]">[URL_MESSAGE_AUTHORIZE_SENDER]</a></p><p>[ENDFOREACH_MESSAGE_SPAM]</p></td></tr><tr><td style="color:#3c4858;font-family:Arial,Helvetica,sans-serif, sans-serif;font-size:14px;">Merci et bonne journée.</td></tr></tbody></table></td></tr></tbody></table></td><td>&nbsp;</td></tr></tbody></table>',

            'reportSpamLevel' => 2,
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
        $crawler = new Crawler($email->getHtmlBody());
        $filter = $crawler->filter('[data-test="' . $category . '"]');

        if ($filter->count() === 0) {
            self::assertSame($count, 0);
            return;
        }

        $occurences =  substr_count($filter->text(), $address);
        self::assertSame($count, $occurences);
    }
}
