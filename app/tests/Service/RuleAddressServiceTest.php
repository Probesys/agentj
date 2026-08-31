<?php

namespace App\Tests\Controller;

use App\Service\RuleAddressService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RuleAddressServiceTest extends WebTestCase
{
    public function testPriority0(): void
    {
        $priority = RuleAddressService::computePriority('@.');

        self::assertEquals(1, $priority);
    }

    public function testPriority1(): void
    {
        $priority = RuleAddressService::computePriority('@.fr');

        self::assertEquals(1, $priority);
    }

    public function testPriority2(): void
    {
        $priority = RuleAddressService::computePriority('@sub.domaine.fr');

        self::assertEquals(5, $priority);
    }

    public function testPriority3(): void
    {
        $priority = RuleAddressService::computePriority('@.sub.domain.fr');

        self::assertEquals(3, $priority);
    }

    public function testPriority5(): void
    {
        $priority = RuleAddressService::computePriority('@domaine.fr');

        self::assertEquals(5, $priority);
    }

    public function testPriority6(): void
    {
        $priority = RuleAddressService::computePriority('@sub.sub.sub.domaine.fr');

        self::assertEquals(5, $priority);
    }
}
