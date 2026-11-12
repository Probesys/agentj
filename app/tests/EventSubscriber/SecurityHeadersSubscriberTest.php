<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\SecurityHeadersSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SecurityHeadersSubscriberTest extends TestCase
{
    public function testAddSecurityHeadersSetsTheSecurityHeaders(): void
    {
        $response = $this->dispatch(Request::create('http://localhost/'));

        self::assertSame(
            SecurityHeadersSubscriber::CONTENT_SECURITY_POLICY,
            $response->headers->get('Content-Security-Policy'),
        );
        self::assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame('same-origin', $response->headers->get('Referrer-Policy'));
        self::assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function testAddSecurityHeadersSetsHstsOverHttps(): void
    {
        $response = $this->dispatch(Request::create('https://localhost/'));

        self::assertSame(
            SecurityHeadersSubscriber::STRICT_TRANSPORT_SECURITY,
            $response->headers->get('Strict-Transport-Security'),
        );
    }

    public function testAddSecurityHeadersKeepsTheHeadersAlreadySet(): void
    {
        $response = new Response();
        $response->headers->set('Content-Security-Policy', "default-src 'none'");

        $response = $this->dispatch(Request::create('http://localhost/'), $response);

        self::assertSame("default-src 'none'", $response->headers->get('Content-Security-Policy'));
        self::assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
    }

    public function testAddSecurityHeadersIgnoresSubRequests(): void
    {
        $response = $this->dispatch(
            Request::create('http://localhost/'),
            requestType: HttpKernelInterface::SUB_REQUEST,
        );

        self::assertFalse($response->headers->has('Content-Security-Policy'));
        self::assertFalse($response->headers->has('X-Frame-Options'));
    }

    private function dispatch(
        Request $request,
        Response $response = new Response(),
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
    ): Response {
        $event = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            $requestType,
            $response,
        );

        new SecurityHeadersSubscriber()->addSecurityHeaders($event);

        return $response;
    }
}
