<?php

namespace App\Tests\Service;

use App\Service\CryptEncryptService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

class CryptEncryptServiceTest extends KernelTestCase
{
    use ClockSensitiveTrait;

    private CryptEncryptService $cryptEncryptService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->cryptEncryptService = static::getContainer()->get(CryptEncryptService::class);
    }

    public function testDecryptReturnsTheEncryptedData(): void
    {
        $clock = static::mockTime(true);

        $token = $this->cryptEncryptService->encrypt('some data', lifetime: 3600);

        [$expiry, $data] = $this->cryptEncryptService->decrypt($token);

        self::assertSame('some data', $data);
        self::assertEquals($clock->now()->getTimestamp() + 3600, $expiry);
    }

    public function testDecryptFailsWithInvalidTokens(): void
    {
        self::expectException(\Exception::class);

        $this->cryptEncryptService->decrypt('invalid token');
    }
}
