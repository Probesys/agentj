<?php

namespace App\Tests;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

trait CommandHelper
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Console\Application
     */
    protected static $application;

    #[Before]
    public static function setUpApplicationTestsHelper(): void
    {
        self::bootKernel();
        self::$application = new Application(self::$kernel);
        self::$application->setAutoExit(false);
    }

    /**
     * @param array<string, mixed> $args
     */
    protected static function executeCommand(
        string $commandName,
        array $args = [],
    ): ApplicationTester {
        $applicationTester = new ApplicationTester(self::$application);
        $parameters = array_merge(['command' => $commandName], $args);
        $applicationTester->run($parameters);
        return $applicationTester;
    }
}
