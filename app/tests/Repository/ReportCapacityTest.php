<?php

namespace App\Tests\Repository;

use App\Repository\ReportDeliveryRepository;
use App\Repository\UserRepository;
use App\Tests\CommandHelper;
use App\Tests\Factory\DomainFactory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ReportCapacityTest extends KernelTestCase
{
    use ClockSensitiveTrait;
    use CommandHelper;
    use Factories;
    use ResetDatabase;

    public function testOneThousandDueUsersAreSelectedAndQueued(): void
    {
        self::mockTime('2026-10-09 09:00 Europe/Paris');
        $domain = DomainFactory::createOne(['reportTimes' => ['09:00', '15:00']]);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $now = new DateTimeImmutable('2026-10-09 09:00 Europe/Paris');
        for ($index = 0; $index < 1000; $index++) {
            $em->getConnection()->insert('users', [
                'email' => 'capacity-' . $index . '@' . $domain->getDomain(),
                'username' => 'capacity-' . $index,
                'domain_id' => $domain->getId(),
                'roles' => '["ROLE_USER"]',
                'report' => true,
                'report_next_at' => $now->getTimestamp(),
            ]);
        }
        $em->clear();
        $repository = self::getContainer()->get(UserRepository::class);
        self::assertCount(1000, $repository->findUsersForReport($now));
        $transport = new InMemoryTransport();
        self::getContainer()->set('messenger.transport.async', $transport);

        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        self::assertCount(1000, $transport->getSent());
        self::assertSame(1000, self::getContainer()->get(ReportDeliveryRepository::class)->count([]));
        self::assertSame([], $repository->findUsersForReport($now));
    }
}
