<?php

namespace App\EventSubscriber;

use App\Service\LocaleService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class UserLocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private LocaleService $localeService,
    ) {
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        /** @var ?\App\Entity\User $user */
        $user = $event->getAuthenticationToken()->getUser();

        $request = $this->requestStack->getCurrentRequest();
        $session = $this->requestStack->getSession();
        $userLocale = $this->localeService->getUserLocale($user);

        $session->set('_locale', $userLocale);
        $request->setLocale($userLocale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }
}
