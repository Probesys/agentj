<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Tests\Factory\DomainFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class UserTest extends KernelTestCase
{
    use Factories;

    public function testAnAddressOfAProtectedDomainIsLocal(): void
    {
        $domain = DomainFactory::new()->withoutPersisting()->create();
        $user = new User();

        $user->setDomain($domain);

        self::assertSame('Y', $user->getLocal());
    }

    public function testAnAddressWithoutDomainIsNotMarkedLocal(): void
    {
        $user = new User();

        $user->setDomain(null);

        self::assertNull($user->getLocal());
    }
}
