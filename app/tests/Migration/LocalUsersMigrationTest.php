<?php

namespace App\Tests\Migration;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MigrationHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class LocalUsersMigrationTest extends KernelTestCase
{
    use Factories;
    use MigrationHelper;
    use ResetDatabase;

    public function testItMarksTheAddressesOfTheDomainsAsLocal(): void
    {
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create(['local' => null]);
        $superAdmin = UserFactory::new()->superAdmin()->create(['local' => null]);
        self::assertNull($user->getLocal());

        self::runMigration('Version20261119100000');

        self::assertSame('Y', UserFactory::find($user->getId())->getLocal());
        self::assertNull(UserFactory::find($superAdmin->getId())->getLocal());
    }
}
