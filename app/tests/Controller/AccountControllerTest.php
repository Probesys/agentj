<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AccountControllerTest extends WebTestCase
{
    use Factories;
    use ClockSensitiveTrait;
    use ResetDatabase;

    public function testUserCanCustomizeScheduleAndRestoreDomainInheritance(): void
    {
        $client = self::createClient();
        $domain = DomainFactory::createOne(['reportTimes' => ['09:00', '15:00']]);
        $user = UserFactory::new()->user($domain)->create();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/account');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-test="report-domain-schedule"] input[value="09:00"]:disabled');
        self::assertSelectorExists('[data-test="report-domain-schedule"] input[value="15:00"]:disabled');
        self::assertSelectorTextContains('#user_preferences_reportTimes_help', 'Leave empty');
        self::assertSelectorExists('input[name="user_preferences[report]"].form-check-input');
        self::assertSelectorExists('input[name="user_preferences[reportSkipWeekends]"].form-check-input');
        self::assertSelectorNotExists('input[name="user_preferences[report]"].form-control');
        self::assertSelectorNotExists('input[name="user_preferences[reportSkipWeekends]"].form-control');
        self::assertSelectorCount(1, 'label[for="user_preferences_report"]');
        self::assertSelectorCount(1, 'label[for="user_preferences_reportSkipWeekends"]');

        $form = $crawler->filter('form')->form([
            'user_preferences[report]' => true,
            'user_preferences[reportTimes]' => '11:00, 17:30',
            'user_preferences[reportSkipWeekends]' => true,
            'user_preferences[reportSkipWeekendsMode]' => 'custom',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('account');
        $updatedUser = UserFactory::find($user->getId());
        self::assertSame(['11:00', '17:30'], $updatedUser->getEffectiveReportTimes());
        self::assertTrue($updatedUser->getEffectiveReportSkipWeekends());

        $crawler = $client->request('GET', '/account');
        $client->submit($crawler->filter('form')->form([
            'user_preferences[reportTimes]' => '',
            'user_preferences[reportSkipWeekendsMode]' => 'inherit',
        ]));

        self::assertResponseRedirects('account');
        $updatedUser = UserFactory::find($user->getId());
        self::assertNull($updatedUser->getReportTimes());
        self::assertNull($updatedUser->getReportSkipWeekends());
        self::assertSame(['09:00', '15:00'], $updatedUser->getEffectiveReportTimes());
        self::assertFalse($updatedUser->getEffectiveReportSkipWeekends());
    }

    #[DataProvider('invalidTimes')]
    public function testInvalidSchedulesAreNotSaved(string $times): void
    {
        $client = self::createClient();
        $user = UserFactory::new()->user()->create();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/account');

        $client->submit($crawler->filter('form')->form([
            'user_preferences[reportTimes]' => $times,
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertNull(UserFactory::find($user->getId())->getReportTimes());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidTimes(): iterable
    {
        yield 'out of range' => ['24:00'];
        yield 'invalid minute' => ['09:60'];
        yield 'incomplete time' => ['9:00'];
        yield 'duplicate times' => ['09:00, 09:00'];
        yield 'empty entry' => ['09:00,'];
    }

    public function testSavingOtherPreferencesPreservesWeekendInheritance(): void
    {
        $client = self::createClient();
        $domain = DomainFactory::createOne(['reportSkipWeekends' => true]);
        $user = UserFactory::new()->user($domain)->create();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/account');
        self::assertSelectorExists('input[name="user_preferences[reportSkipWeekends]"]:checked');
        self::assertSelectorTextContains('[data-test="report-weekend-origin"]', 'domain setting');

        $client->submit($crawler->filter('form[name="user_preferences"]')->form([
            'user_preferences[humanAuthenticationEnabled]' => false,
        ]));

        self::assertResponseRedirects('account');
        $updated = UserFactory::find($user->getId());
        self::assertNull($updated->getReportSkipWeekends());
        self::assertTrue($updated->getEffectiveReportSkipWeekends());
    }

    public function testWeekendChoiceAndReturnToInheritanceWorkWithoutJavaScript(): void
    {
        $client = self::createClient();
        $domain = DomainFactory::createOne(['reportSkipWeekends' => true]);
        $user = UserFactory::new()->user($domain)->create(['reportTimes' => ['11:00']]);
        $client->loginUser($user);
        $crawler = $client->request('GET', '/account');
        self::assertSelectorExists('select[name="user_preferences[reportSkipWeekendsMode]"] option[value="inherit"]');
        self::assertSelectorExists('select[name="user_preferences[reportSkipWeekendsMode]"] option[value="custom"]');

        $form = $crawler->filter('form[name="user_preferences"]')->form();
        $mode = $form['user_preferences[reportSkipWeekendsMode]'];
        $weekends = $form['user_preferences[reportSkipWeekends]'];
        self::assertInstanceOf(ChoiceFormField::class, $mode);
        self::assertInstanceOf(ChoiceFormField::class, $weekends);
        $mode->select('custom');
        $weekends->untick();
        $client->submit($form);
        self::assertResponseRedirects('account');
        self::assertFalse(UserFactory::find($user->getId())->getReportSkipWeekends());

        $crawler = $client->request('GET', '/account');
        $form = $crawler->filter('form[name="user_preferences"]')->form();
        $mode = $form['user_preferences[reportSkipWeekendsMode]'];
        self::assertInstanceOf(ChoiceFormField::class, $mode);
        $mode->select('inherit');
        $client->submit($form);
        self::assertResponseRedirects('account');
        $updated = UserFactory::find($user->getId());
        self::assertNull($updated->getReportSkipWeekends());
        self::assertTrue($updated->getEffectiveReportSkipWeekends());
        self::assertSame(['11:00'], $updated->getReportTimes());
    }

    #[DataProvider('personalWeekendChoices')]
    public function testExplicitCheckboxChoiceWinsWithoutPersonalSendingTimes(bool $choice): void
    {
        $client = self::createClient();
        $domain = DomainFactory::createOne(['reportSkipWeekends' => !$choice]);
        $user = UserFactory::new()->user($domain)->create();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/account');

        $client->submit($crawler->filter('form[name="user_preferences"]')->form([
            'user_preferences[reportSkipWeekends]' => $choice,
            'user_preferences[reportSkipWeekendsMode]' => 'custom',
        ]));

        self::assertResponseRedirects('account');
        $updated = UserFactory::find($user->getId());
        self::assertNull($updated->getReportTimes());
        self::assertSame($choice, $updated->getReportSkipWeekends());
        self::assertSame($choice, $updated->getEffectiveReportSkipWeekends());
    }

    #[DataProvider('personalWeekendChoices')]
    public function testChoiceMatchingTheDomainStillBecomesPersonal(bool $choice): void
    {
        $client = self::createClient();
        $domain = DomainFactory::createOne(['reportSkipWeekends' => $choice]);
        $user = UserFactory::new()->user($domain)->create();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/account');

        $client->submit($crawler->filter('form[name="user_preferences"]')->form([
            'user_preferences[reportSkipWeekends]' => $choice,
            'user_preferences[reportSkipWeekendsMode]' => 'custom',
        ]));

        self::assertResponseRedirects('account');
        $updated = UserFactory::find($user->getId());
        self::assertSame($choice, $updated->getReportSkipWeekends());
        $currentDomain = $updated->getDomain();
        self::assertNotNull($currentDomain);
        $currentDomain->setReportSkipWeekends(!$choice);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        self::assertSame($choice, $updated->getEffectiveReportSkipWeekends());
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function personalWeekendChoices(): iterable
    {
        yield 'checked' => [true];
        yield 'explicitly unchecked' => [false];
    }

    public function testClearingSendingTimesDoesNotClearTheWeekendChoice(): void
    {
        $client = self::createClient();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create([
            'reportTimes' => ['11:00'],
            'reportSkipWeekends' => true,
        ]);
        $client->loginUser($user);
        $crawler = $client->request('GET', '/account');

        $client->submit($crawler->filter('form[name="user_preferences"]')->form([
            'user_preferences[reportTimes]' => '',
        ]));

        self::assertResponseRedirects('account');
        $updated = UserFactory::find($user->getId());
        self::assertNull($updated->getReportTimes());
        self::assertTrue($updated->getReportSkipWeekends());
        self::assertTrue($updated->getEffectiveReportSkipWeekends());
    }

    public function testDomainDefaultsResetBothPreferencesWithoutEnablingReports(): void
    {
        $client = self::createClient();
        $domain = DomainFactory::createOne(['reportTimes' => ['09:00'], 'reportSkipWeekends' => true]);
        $user = UserFactory::new()->user($domain)->create([
            'report' => false,
            'reportTimes' => ['11:00'],
            'reportSkipWeekends' => false,
        ]);
        $client->loginUser($user);
        $crawler = $client->request('GET', '/account');
        self::assertSelectorExists('[data-test="report-domain-defaults"][type="checkbox"]');
        self::assertSelectorExists('select[data-report-preferences-target="mode"]');
        self::assertSelectorExists('input[data-report-preferences-target="weekends"]');

        $client->submit($crawler->filter('form[name="user_preferences"]')->form([
            'user_preferences[reportTimes]' => '',
            'user_preferences[reportSkipWeekendsMode]' => 'inherit',
        ]));

        self::assertResponseRedirects('account');
        $updated = UserFactory::find($user->getId());
        self::assertNull($updated->getReportTimes());
        self::assertNull($updated->getReportSkipWeekends());
        self::assertFalse($updated->getReport());
        self::assertSame(['09:00'], $updated->getEffectiveReportTimes());
        self::assertTrue($updated->getEffectiveReportSkipWeekends());
    }

    public function testWeekdayChoiceRecalculatesTheInheritedSchedule(): void
    {
        $client = self::createClient();
        self::mockTime('2026-10-10 08:00 Europe/Paris');
        $domain = DomainFactory::createOne(['reportTimes' => ['09:00'], 'reportSkipWeekends' => true]);
        $user = UserFactory::new()->user($domain)->create();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/account');

        $client->submit($crawler->filter('form[name="user_preferences"]')->form([
            'user_preferences[reportSkipWeekends]' => false,
            'user_preferences[reportSkipWeekendsMode]' => 'custom',
        ]));

        self::assertResponseRedirects('account');
        self::assertSame(
            new DateTimeImmutable('2026-10-10 09:00 Europe/Paris')->getTimestamp(),
            UserFactory::find($user->getId())->getReportNextAt(),
        );
    }

    public function testDomainChangeWhileTheFormIsOpenDoesNotCreateAPersonalChoice(): void
    {
        $client = self::createClient();
        $domain = DomainFactory::createOne(['reportSkipWeekends' => false]);
        $user = UserFactory::new()->user($domain)->create();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/account');
        $form = $crawler->filter('form[name="user_preferences"]')->form();
        self::getContainer()->get(EntityManagerInterface::class)->getConnection()->update(
            'domain',
            ['report_skip_weekends' => 1],
            ['id' => $domain->getId()],
        );

        $client->submit($form);

        self::assertResponseRedirects('account');
        $updated = UserFactory::find($user->getId());
        self::assertNull($updated->getReportSkipWeekends());
        self::assertTrue($updated->getEffectiveReportSkipWeekends());
    }
}
