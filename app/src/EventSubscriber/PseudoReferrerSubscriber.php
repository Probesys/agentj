<?php

namespace App\EventSubscriber;

use App\Service\Referrer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This subscriber saves the current (GET) URI in session to be used later as
 * referrer if the Referer header is missing.
 *
 * The URI is saved at the end of the request, so getting the pseudo referrer
 * in a GET request doesn't return the current URI, but the previous one.
 */
class PseudoReferrerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Referrer $referrer,
    ) {
    }

    public function savePseudoReferrer(FinishRequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // Ignore sub-requests such as embedded controllers or internal
            // redirects.
            return;
        }

        $this->referrer->savePseudoReferrer();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::FINISH_REQUEST => 'savePseudoReferrer',
        ];
    }
}
