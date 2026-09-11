<?php

namespace App\Tests\Repository;

use App\Repository\RuleAddressRepository;
use App\Tests\Factory\RuleAddressFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RuleAddressRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testFindOneOrCreateNormalizesAndReusesRuleAddress(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(RuleAddressRepository::class);

        $first = $repository->findOneOrCreateByEmail('Sender@Example.ORG');
        $second = $repository->findOneOrCreateByEmail('sender@example.org');

        self::assertSame($first->getId(), $second->getId());
        self::assertSame('sender@example.org', $first->getEmail());
        self::assertSame(6, $first->getPriority());
        self::assertSame(1, RuleAddressFactory::count(['email' => 'sender@example.org']));
    }

    public function testFindOneOrCreateComputesDomainPriority(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(RuleAddressRepository::class);

        $ruleAddress = $repository->findOneOrCreateByEmail('@.Example.ORG');

        self::assertSame('@.example.org', $ruleAddress->getEmail());
        self::assertSame(2, $ruleAddress->getPriority());
    }

    public function testFindOneOrCreateComputesRootPriority(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(RuleAddressRepository::class);

        $ruleAddress = $repository->findOneOrCreateByEmail('@.');

        self::assertSame(0, $ruleAddress->getPriority());
    }
}
