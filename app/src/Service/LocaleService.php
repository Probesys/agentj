<?php

namespace App\Service;

use App\Entity\User;

class LocaleService
{
    /**
     * Get the locale for a user based on their preferences
     * Priority: User's preferred lang > Domain's default lang > 'fr'
     */
    public function getUserLocale(?User $user): string
    {
        if ($user === null) {
            return 'fr';
        }

        if ($user->getPreferedLang() !== null) {
            return $user->getPreferedLang();
        }

        if ($user->getDomain() && $user->getDomain()->getDefaultLang() !== null) {
            return $user->getDomain()->getDefaultLang();
        }

        return 'fr';
    }
}
