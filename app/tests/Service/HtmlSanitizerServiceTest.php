<?php

namespace App\Tests\Service;

use App\Service\HtmlSanitizerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class HtmlSanitizerServiceTest extends TestCase
{
    public function testIsTooLargeComparesTheLengthInBytesWithTheLimit(): void
    {
        $service = new HtmlSanitizerService(
            $this->createStub(HtmlSanitizerInterface::class),
            10
        );

        self::assertFalse($service->isTooLarge(str_repeat('a', 10)));
        self::assertTrue($service->isTooLarge(str_repeat('a', 11)));
        // 2 bytes per character
        self::assertTrue($service->isTooLarge(str_repeat('é', 6)));
    }

    public function testSanitizeFailsIfTheHtmlIsTooLarge(): void
    {
        $service = new HtmlSanitizerService(
            $this->createStub(HtmlSanitizerInterface::class),
            10
        );

        $this->expectException(\LogicException::class);
        $service->sanitize(str_repeat('a', 11));
    }
}
