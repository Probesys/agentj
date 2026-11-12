<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This subscriber adds security headers to every response.
 *
 * A header already set on the response (e.g. by a controller) is kept, so
 * that a page can use a stricter policy than the default one.
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    /**
     * Inline scripts and styles are still allowed, as the templates use them.
     * Altcha computes its challenge in a worker created from a blob: URL.
     */
    public const CONTENT_SECURITY_POLICY = "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data:; "
        . "worker-src 'self' blob:; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "frame-ancestors 'self'";

    public const STRICT_TRANSPORT_SECURITY = 'max-age=31536000';

    public function addSecurityHeaders(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $securityHeaders = [
            'Content-Security-Policy' => self::CONTENT_SECURITY_POLICY,
            // The message content is displayed in an iframe of the same origin.
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            // The Referer header is used to redirect to the previous page.
            'Referrer-Policy' => 'same-origin',
        ];

        // Browsers ignore this header over HTTP.
        if ($event->getRequest()->isSecure()) {
            $securityHeaders['Strict-Transport-Security'] = self::STRICT_TRANSPORT_SECURITY;
        }

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
