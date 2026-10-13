<?php

namespace App\Tests\Repository;

use App\Entity\Log;
use App\Repository\LogRepository;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\LogFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class LogRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private LogRepository $logRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->logRepository = static::getContainer()->get(LogRepository::class);
    }

    public function testGetSearchQueryReturnsAllTheEntriesToSuperAdmins(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        LogFactory::createOne(['domain' => $domain]);
        LogFactory::createOne(['domain' => null]);

        $logs = $this->logRepository->getSearchQuery($superAdmin)->getResult();

        self::assertCount(2, $logs);
    }

    public function testGetSearchQueryReturnsOnlyTheEntriesOfManagedDomainsToAdmins(): void
    {
        $managedDomain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$managedDomain])->create();
        $managedLog = LogFactory::createOne(['domain' => $managedDomain]);
        LogFactory::createOne(['domain' => $otherDomain]);
        // Older entries and global actions have no domain
        LogFactory::createOne(['domain' => null]);

        $logs = $this->logRepository->getSearchQuery($admin)->getResult();

        self::assertSame([$managedLog->getId()], $this->ids($logs));
    }

    public function testGetSearchQueryReturnsNoEntriesToAdminsWithoutDomain(): void
    {
        $admin = UserFactory::new()->admin([])->create();
        LogFactory::createOne(['domain' => DomainFactory::createOne()]);
        LogFactory::createOne(['domain' => null]);

        $logs = $this->logRepository->getSearchQuery($admin)->getResult();

        self::assertCount(0, $logs);
    }

    public function testGetSearchQueryDoesNotReturnTheEntriesOfAnotherDomainFilteredByAdmins(): void
    {
        $managedDomain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$managedDomain])->create();
        LogFactory::createOne(['domain' => $otherDomain]);

        $logs = $this->logRepository->getSearchQuery($admin, ['domain' => $otherDomain])->getResult();

        self::assertCount(0, $logs);
    }

    public function testGetSearchQueryFiltersByDates(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        LogFactory::createOne(['created' => new \DateTime('2026-10-01 23:59:59')]);
        $firstDayLog = LogFactory::createOne(['created' => new \DateTime('2026-10-02 00:00:00')]);
        $lastDayLog = LogFactory::createOne(['created' => new \DateTime('2026-10-03 23:59:59')]);
        LogFactory::createOne(['created' => new \DateTime('2026-10-04 00:00:00')]);

        $logs = $this->logRepository->getSearchQuery($superAdmin, [
            'startDate' => new \DateTimeImmutable('2026-10-02'),
            'endDate' => new \DateTimeImmutable('2026-10-03'),
        ])->getResult();

        self::assertSame([$lastDayLog->getId(), $firstDayLog->getId()], $this->ids($logs));
    }

    public function testGetSearchQueryFiltersByActor(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create(['username' => 'localadmin']);
        $alice = UserFactory::createOne(['email' => 'alice@example.org', 'fullname' => 'Alice Martin']);
        $bob = UserFactory::createOne(['email' => 'bob@example.org', 'fullname' => 'Bob Durand']);
        $aliceLog = LogFactory::createOne(['createdBy' => $alice]);
        LogFactory::createOne(['createdBy' => $bob]);
        LogFactory::createOne(['createdBy' => null]);
        $localAdminLog = LogFactory::createOne(['createdBy' => $superAdmin]);

        $logs = $this->logRepository->getSearchQuery($superAdmin, ['actor' => 'ALICE@'])->getResult();
        self::assertSame([$aliceLog->getId()], $this->ids($logs));
        $logs = $this->logRepository->getSearchQuery($superAdmin, ['actor' => 'martin'])->getResult();
        self::assertSame([$aliceLog->getId()], $this->ids($logs));
        // The local admins may have no email
        $logs = $this->logRepository->getSearchQuery($superAdmin, ['actor' => 'localadmin'])->getResult();
        self::assertSame([$localAdminLog->getId()], $this->ids($logs));
    }

    public function testGetSearchQueryFiltersByActionAndMailId(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $expectedLog = LogFactory::createOne(['action' => 'banned', 'mailId' => 'abc']);
        LogFactory::createOne(['action' => 'banned', 'mailId' => 'def']);
        LogFactory::createOne(['action' => 'restore', 'mailId' => 'abc']);

        $logs = $this->logRepository->getSearchQuery($superAdmin, [
            'action' => 'banned',
            'mailId' => 'abc',
        ])->getResult();

        self::assertSame([$expectedLog->getId()], $this->ids($logs));
    }

    public function testGetSearchQueryFiltersByDomain(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $domainLog = LogFactory::createOne(['domain' => $domain]);
        LogFactory::createOne(['domain' => DomainFactory::createOne()]);
        LogFactory::createOne(['domain' => null]);

        $logs = $this->logRepository->getSearchQuery($superAdmin, ['domain' => $domain])->getResult();

        self::assertSame([$domainLog->getId()], $this->ids($logs));
    }

    public function testFindActionsReturnsTheDistinctActions(): void
    {
        LogFactory::createOne(['action' => 'restore']);
        LogFactory::createOne(['action' => 'banned']);
        LogFactory::createOne(['action' => 'restore']);

        self::assertSame(['banned', 'restore'], $this->logRepository->findActions());
    }

    /**
     * @param Log[] $logs
     *
     * @return int[]
     */
    private function ids(array $logs): array
    {
        return array_map(fn (Log $log): int => $log->getId(), $logs);
    }
}
