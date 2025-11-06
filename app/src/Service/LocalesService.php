<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @phpstan-type LocaleCode key-of<self::SUPPORTED_LOCALES>
 */
class LocalesService
{
    public const SUPPORTED_LOCALES = [
        'en' => 'English',
        'fr' => 'Français',
    ];

    /** @var LocaleCode */
    private string $defaultLocale;

    /**
     * @return array<LocaleCode>
     */
    public static function getSupportedLocalesCodes(): array
    {
        return array_keys(self::SUPPORTED_LOCALES);
    }

    public function __construct(
        #[Autowire(env: 'DEFAULT_LOCALE')]
        string $defaultLocale,
    ) {
        if ($this->isAvailable($defaultLocale)) {
            /** @var LocaleCode */
            $defaultLocale = $defaultLocale;
            $this->defaultLocale = $defaultLocale;
        } else {
            $this->defaultLocale = 'en';
        }
    }

    /**
     * @return LocaleCode
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function isAvailable(string $locale): bool
    {
        return isset(self::SUPPORTED_LOCALES[$locale]);
    }
}
