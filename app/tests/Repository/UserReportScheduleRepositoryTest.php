<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserReportScheduleRepositoryTest extends KernelTestCase
{
    use Factories;
    use ClockSensitiveTrait;
    use ResetDatabase;

    public function testOnlyDueRecipientsAndTheirDomainsAreLoaded(): void
    {
        self::bootKernel();
        self::mockTime('2026-10-09 08:00 Europe/Paris');
        $now = new DateTimeImmutable('2026-10-09 09:00:30 Europe/Paris');
        $domain = DomainFactory::createOne(['reportTimes' => ['09:00', '15:00']]);
        $firstReport = UserFactory::new()->user($domain)->create();
        $previousReport = UserFactory::new()->user($domain)->create([
            'dateLastReport' => $now->setTime(8, 59, 59)->getTimestamp(),
        ]);
        $customDomain = DomainFactory::createOne(['reportTimes' => ['11:00']]);
        $customReport = UserFactory::new()->user($customDomain)->create(['reportTimes' => ['09:00']]);
        $notDue = UserFactory::new()->user($domain)->create(['reportTimes' => ['11:00']]);
        $alreadySent = UserFactory::new()->user($domain)->create([
            'dateLastReport' => $now->setTime(9, 0)->getTimestamp(),
        ]);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $alreadySent->setReportNextAt($now->setTime(15, 0)->getTimestamp());
        $em->flush();
        UserFactory::new()->user($domain)->create(['report' => false]);
        UserFactory::new()->user($domain)->alias($firstReport)->create();
        UserFactory::new()->admin([$domain])->create(['domain' => $domain]);
        $inactiveDomain = DomainFactory::createOne(['active' => false, 'reportTimes' => ['09:00']]);
        UserFactory::new()->user($inactiveDomain)->create();

        $em->clear();
        $repository = self::getContainer()->get(UserRepository::class);
        $users = $repository->findUsersForReport($now);

        $expectedIds = [$firstReport->getId(), $previousReport->getId(), $customReport->getId()];
        self::assertEqualsCanonicalizing($expectedIds, array_map(fn (User $user) => $user->getId(), $users));
        self::assertCount(3, $em->getUnitOfWork()->getIdentityMap()[User::class]);
        foreach ($users as $user) {
            self::assertFalse($em->isUninitializedObject($user->getDomain()));
        }

        $em->clear();
        $forcedUsers = $repository->findUsersForReport($now, force: true);
        self::assertEqualsCanonicalizing(
            [...$expectedIds, $notDue->getId(), $alreadySent->getId()],
            array_map(fn (User $user) => $user->getId(), $forcedUsers),
        );
    }

    public function testNoUsersAreLoadedWhenNoReportIsDue(): void
    {
        self::bootKernel();
        self::mockTime('2026-10-09 08:00 Europe/Paris');
        $domain = DomainFactory::createOne(['reportTimes' => ['11:00']]);
        UserFactory::new()->user($domain)->create();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        $users = self::getContainer()->get(UserRepository::class)->findUsersForReport(
            new DateTimeImmutable('2026-10-09 09:00:00 Europe/Paris'),
        );

        self::assertSame([], $users);
        self::assertSame([], $em->getUnitOfWork()->getIdentityMap());
    }

    public function testWeekendSelectionUsesTheEffectiveSchedule(): void
    {
        self::bootKernel();
        self::mockTime('2026-10-10 08:00 Europe/Paris');
        $now = new DateTimeImmutable('2026-10-10 09:00:00 Europe/Paris');
        $domain = DomainFactory::createOne(['reportTimes' => ['09:00'], 'reportSkipWeekends' => true]);
        $inheritedSkip = UserFactory::new()->user($domain)->create();
        $customAllow = UserFactory::new()->user($domain)->create([
            'reportTimes' => ['09:00'],
            'reportSkipWeekends' => false,
        ]);
        $otherDomain = DomainFactory::createOne(['reportTimes' => ['09:00']]);
        $inheritedAllow = UserFactory::new()->user($otherDomain)->create();
        $customSkip = UserFactory::new()->user($otherDomain)->create([
            'reportTimes' => ['09:00'],
            'reportSkipWeekends' => true,
        ]);
        $allowWithoutPersonalTimes = UserFactory::new()->user($domain)->create(['reportSkipWeekends' => false]);
        $skipWithoutPersonalTimes = UserFactory::new()->user($otherDomain)->create(['reportSkipWeekends' => true]);
        $skipWithoutPersonalTimes->setReportNextAt($now->modify('-1 day')->getTimestamp());
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        $repository = self::getContainer()->get(UserRepository::class);

        $users = $repository->findUsersForReport($now);

        self::assertEqualsCanonicalizing(
            [$customAllow->getId(), $inheritedAllow->getId(), $allowWithoutPersonalTimes->getId()],
            array_map(fn (User $user) => $user->getId(), $users),
        );
        $forcedUsers = $repository->findUsersForReport($now, force: true);
        self::assertEqualsCanonicalizing(
            [
                $customAllow->getId(), $inheritedAllow->getId(), $inheritedSkip->getId(), $customSkip->getId(),
                $allowWithoutPersonalTimes->getId(), $skipWithoutPersonalTimes->getId(),
            ],
            array_map(fn (User $user) => $user->getId(), $forcedUsers),
        );
    }
}
