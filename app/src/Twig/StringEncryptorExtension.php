<?php

namespace App\Twig;

use App\Service\CryptEncryptService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class StringEncryptorExtension extends AbstractExtension
{
    public function __construct(private CryptEncryptService $cryptEncryptService)
    {
    }


    public function getFunctions(): array
    {
        return [
            new TwigFunction('encrypt', [$this, 'encrypt']),
        ];
    }

    public function encrypt(string $strInput, int $lifetime = 48 * 3600): string
    {

        return $this->cryptEncryptService->encrypt($strInput, $lifetime);
    }
}
