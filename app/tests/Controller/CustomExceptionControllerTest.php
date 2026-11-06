<?php

namespace App\Tests\Controller;

use App\Controller\CustomExceptionController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomExceptionControllerTest extends KernelTestCase
{
    public function testShowsTheTranslatedTitleWhenItExists(): void
    {
        $response = $this->renderError(new NotFoundHttpException());

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $translator = self::getContainer()->get(TranslatorInterface::class);
        self::assertInstanceOf(TranslatorInterface::class, $translator);
        self::assertSame($translator->trans('Message.http.404.title'), $this->getTitle($response));
    }

    public function testShowsTheStatusTextWhenTheTitleIsNotTranslated(): void
    {
        // There is no translation for the 405 error.
        $response = $this->renderError(new MethodNotAllowedHttpException(['POST']));

        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
        self::assertSame('405 Method Not Allowed', $this->getTitle($response));
    }

    public function testEscapesTheExceptionMessage(): void
    {
        // Exception messages can contain user input, e.g. the Host header
        // for the "Invalid Host" 400 error.
        $response = $this->renderError(
            new MethodNotAllowedHttpException(['POST'], '<script>alert("xss")</script>'),
        );

        $content = (string) $response->getContent();
        self::assertStringNotContainsString('<script>alert("xss")</script>', $content);
        self::assertStringContainsString('&lt;script&gt;', $content);
    }

    private function renderError(\Throwable $exception): Response
    {
        self::bootKernel();
        $container = self::getContainer();

        // The error template needs the current request (e.g. for the locale).
        $requestStack = $container->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $requestStack);
        $requestStack->push(Request::create('/'));

        $controller = $container->get(CustomExceptionController::class);
        self::assertInstanceOf(CustomExceptionController::class, $controller);

        return $controller->showAction($exception);
    }

    private function getTitle(Response $response): string
    {
        $crawler = new Crawler((string) $response->getContent());

        return $crawler->filter('h1')->text();
    }
}
