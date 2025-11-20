<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @phpstan-type LocaleCode key-of<self::SUPPORTED_LOCALES>
 */
class LocaleService
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

    /**
     * Get the locale for a user based on their preferences
     * Priority: User's preferred lang > Domain's default lang > Default locale
     *
     * @return LocaleCode
     */
    public function getUserLocale(?User $user): string
    {
        if ($user === null) {
            return $this->defaultLocale;
        }

        if ($user->getPreferedLang() !== null && $this->isAvailable($user->getPreferedLang())) {
            /** @var LocaleCode */
            $locale = $user->getPreferedLang();
            return $locale;
        }

        if ($user->getDomain() && $user->getDomain()->getDefaultLang() !== null && $this->isAvailable($user->getDomain()->getDefaultLang())) {
            /** @var LocaleCode */
            $locale = $user->getDomain()->getDefaultLang();
            return $locale;
        }

        return $this->defaultLocale;
    }
}
