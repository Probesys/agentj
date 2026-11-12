<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This subscriber adds security headers to every response.
 *
 * A header already set on the response (e.g. by a controller) is kept, so
 * that a page can use its own policy instead of the default one.
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    /**
     * Inline scripts and styles are still allowed, as the templates use them.
     */
    public const CONTENT_SECURITY_POLICY = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline'",
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self' data:",
        "object-src 'none'",
        "base-uri 'self'",
        "frame-ancestors 'self'",
    ];

    public function addSecurityHeaders(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $securityHeaders = [
            'Content-Security-Policy' => implode('; ', self::CONTENT_SECURITY_POLICY),
            // The message content is displayed in an iframe of the same origin.
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            // The Referer header is used to redirect to the previous page.
            'Referrer-Policy' => 'same-origin',
        ];

        $headers = $event->getResponse()->headers;
        foreach ($securityHeaders as $name => $value) {
            if (!$headers->has($name)) {
                $headers->set($name, $value);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'addSecurityHeaders',
        ];
    }
}
