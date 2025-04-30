<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Twig\Environment;

class CustomExceptionController extends AbstractController
{
    public function __construct(private Environment $twig)
    {
    }

    public function showAction(\Throwable $exception): Response
    {
        $statusCode = $exception instanceof HttpExceptionInterface ? 
            $exception->getStatusCode() : 
            Response::HTTP_INTERNAL_SERVER_ERROR;

        return new Response(
            $this->twig->render('bundles/TwigBundle/Exception/error.html.twig', [
                'exception' => $exception,
                'status_code' => $statusCode,
                'status_text' => Response::$statusTexts[$statusCode] ?? ''
            ]),
            $statusCode
        );
    }
}