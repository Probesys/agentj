<?php

namespace App\Tests\Service;

use App\Service\CryptEncryptService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CryptEncryptServiceTest extends KernelTestCase
{
    private CryptEncryptService $cryptEncryptService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->cryptEncryptService = static::getContainer()->get(CryptEncryptService::class);
    }

    public function testDecryptReturnsTheEncryptedData(): void
    {
        $token = $this->cryptEncryptService->encrypt('some data', lifetime: 3600);

        [$expiry, $data] = $this->cryptEncryptService->decrypt($token);

        self::assertSame('some data', $data);
        self::assertEqualsWithDelta(time() + 3600, $expiry, 5);
    }

    public function testDecryptRejectsInvalidTokens(): void
    {
        [$expiry, $data] = $this->cryptEncryptService->decrypt('invalid token');

        self::assertFalse($data);
        self::assertSame(0, $expiry);
    }
}
