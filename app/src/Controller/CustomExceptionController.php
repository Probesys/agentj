<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Twig\Environment;


use App\Controller\MessageController;
use App\Model\ValidationSource;
use App\Entity\Domain;
use App\Entity\MessageStatus;
use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use App\Entity\User;
use App\Entity\Alert;
use App\Form\CaptchaFormType;
use App\Service\CryptEncryptService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomExceptionController extends AbstractController
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
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