<?php

namespace App\EventSubscriber;

use App\Service\LocaleService;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LocaleService $localeService,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $supportedLocalesCodes = $this->localeService->getSupportedLocalesCodes();
        $locale = $request->getPreferredLanguage($supportedLocalesCodes);

        if ($request->hasPreviousSession()) {
            $locale = $request->getSession()->get('_locale', $locale);
        }

        $request->setLocale($locale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
