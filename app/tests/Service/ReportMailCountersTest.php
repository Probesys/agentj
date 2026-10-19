<?php

namespace App\Tests\Service;

use App\Amavis\MessageStatus;
use App\Entity\User;
use App\Service\ReportMailBuilder;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ReportMailCountersTest extends KernelTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    public function testAllCountersUseTheSamePreparationPeriod(): void
    {
        self::bootKernel();
        $domain = DomainFactory::createOne(['messageAlert' => implode(' ', [
            'untreated=[NB_UNTREATED_MESSAGES]', 'authorized=[NB_AUTHORIZED_MESSAGES]',
            'banned=[NB_BANNED_MESSAGES]', 'deleted=[NB_DELETED_MESSAGES]',
            'restored=[NB_RESTORED_MESSAGES]', 'spam=[NB_SPAMMED_MESSAGES]', 'blocked=[NB_BLOCKED_MESSAGES]',
        ])]);
        $user = UserFactory::new()->user($domain)->create([
            'reportPeriodEnd' => $this->timestamp('09:00'),
            'dateLastReport' => $this->timestamp('09:05'),
        ]);
        $statuses = [
            MessageStatus::UNTREATED, MessageStatus::AUTHORIZED, MessageStatus::BANNED,
            MessageStatus::DELETED, MessageStatus::RESTORED, MessageStatus::SPAMMED, MessageStatus::VIRUS,
        ];
        foreach ($statuses as $status) {
            $this->createMessage($user, '09:00', $status);
            $this->createMessage($user, '09:02', $status);
            $this->createMessage($user, '15:00', $status);
            $this->createMessage($user, '15:00:01', $status);
        }

        $content = self::getContainer()->get(ReportMailBuilder::class)->prepare($user, $this->timestamp('15:00'));

        self::assertNotNull($content);
        self::assertSame('untreated=2 authorized=2 banned=2 deleted=2 restored=2 spam=2 blocked=6', $content['html']);
    }

    #[DataProvider('defaultTemplateLocales')]
    public function testDefaultTemplateUsesPeriodCountersInsteadOfTheWholeStock(
        string $locale,
        string $untreatedText,
        string $blockedText,
    ): void {
        self::bootKernel();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create([
            'reportPeriodEnd' => $this->timestamp('09:00'),
            'preferedLang' => $locale,
        ]);
        $this->createMessage($user, '08:30', MessageStatus::UNTREATED);
        $this->createMessage($user, '10:00', MessageStatus::UNTREATED);
        $this->createMessage($user, '08:30', MessageStatus::SPAMMED);
        $this->createMessage($user, '10:00', MessageStatus::SPAMMED);
        $this->createMessage($user, '11:00', MessageStatus::BANNED);
        $this->createMessage($user, '12:00', MessageStatus::VIRUS);

        $content = self::getContainer()->get(ReportMailBuilder::class)->prepare($user, $this->timestamp('15:00'));

        self::assertNotNull($content);
        self::assertStringContainsString($untreatedText, $content['html']);
        self::assertStringContainsString($blockedText, $content['html']);
        self::assertStringNotContainsString('[NB_', $content['html']);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function defaultTemplateLocales(): iterable
    {
        yield 'English' => ['en', '1 emails that are untreated', '3 messages that were blocked'];
        yield 'French' => ['fr', '1 mails qui sont en attentes de validation', '3 messages qui ont été bloqués'];
    }

    public function testFirstReportCountersIncludeAllExistingMessages(): void
    {
        self::bootKernel();
        $domain = DomainFactory::createOne(['messageAlert' => '[NB_UNTREATED_MESSAGES]']);
        $user = UserFactory::new()->user($domain)->create();
        $this->createMessage($user, '08:30', MessageStatus::UNTREATED);
        $this->createMessage($user, '10:00', MessageStatus::UNTREATED);
        $this->createMessage($user, '16:00', MessageStatus::UNTREATED);

        $content = self::getContainer()->get(ReportMailBuilder::class)->prepare($user, $this->timestamp('15:00'));

        self::assertNotNull($content);
        self::assertSame('2', $content['html']);
    }

    private function createMessage(User $user, string $time, int $status): void
    {
        $sender = AddressFactory::findOrCreate(['email' => 'sender@example.net', 'partitionTag' => 0]);
        $recipient = AddressFactory::findOrCreate(['email' => $user->getEmail(), 'partitionTag' => 0]);
        $this->setupMail($sender, [$recipient], status: $status, messageAttributes: [
            'timeNum' => $this->timestamp($time),
        ]);
    }

    private function timestamp(string $time): int
    {
        return new DateTimeImmutable('2026-10-09 ' . $time . ' Europe/Paris')->getTimestamp();
    }
}
