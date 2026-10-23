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

    public function testDecryptRejectsTheTokensOfTheFormerFormat(): void
    {
        // Former (v1) format: the expiry was not encrypted, but prepended in
        // clear to the ciphertext
        $key = static::getContainer()->getParameter('salt');
        $iv = static::getContainer()->getParameter('iv');
        $ciphertext = openssl_encrypt('some data', 'aes-256-cbc', $key, 0, $iv);
        self::assertNotFalse($ciphertext);
        $token = base64_encode((time() + 3600) . $ciphertext);

        [$expiry, $data] = $this->cryptEncryptService->decrypt($token);

        self::assertFalse($data);
        self::assertSame(0, $expiry);
    }

    public function testDecryptRejectsInvalidTokens(): void
    {
        [$expiry, $data] = $this->cryptEncryptService->decrypt('invalid token');

        self::assertFalse($data);
        self::assertSame(0, $expiry);
    }
}
