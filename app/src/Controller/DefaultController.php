<?php

namespace App\Controller;

use App\Amavis\ContentType;
use App\Amavis\MessageStatus;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\MsgrcptRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    public function __construct(
        private Security $security,
    ) {
    }

    #[Route(path: '/', name: 'homepage')]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $showAlertWidget = true;
        if ($user->getDomain() && $user->getDomain()->getSendUserAlerts() == false) {
            $showAlertWidget = false;
        }

        return $this->render('home/index.html.twig', [
                    'controller_name' => 'DefaultController',
                    'showAlert' => $showAlertWidget
        ]);
    }


    /**
     * Change the locale for the current user.
     */
    #[Route(path: '/setlocale/{language}', name: 'setlocale')]
    public function setLocaleAction(Request $request, EntityManagerInterface $em, ?string $language = null): Response
    {
        if (null != $language) {
            $request->getSession()->set('_locale', $language);
        }

        $url = $request->headers->get('referer');
        if (empty($url)) {
            $url = $this->container->get('router')->generate('message');
        }

        /** @var User $user */
        $user = $this->getUser();
        if (!$this->security->isGranted('ROLE_PREVIOUS_ADMIN')) {
            $user->setPreferedLang($language);
            $em->persist($user);
            $em->flush();
        }

        $user->setPreferedLang($language);
        $em->persist($user);
        $em->flush();

        return new RedirectResponse($url);
    }

    #[Route('/per_page/{nbItems}', name: 'per_page')]
    public function perPage(int $nbItems, Request $request): Response
    {
        $referer = $request->headers->get('referer');
        $session = $request->getSession();
        $session->set('perPage', $nbItems);
        $urlParts = parse_url($referer);
        parse_str($urlParts['query'] ?? '', $query);
        $query['page'] = 1;
        $newQuery = http_build_query($query);
        $newReferer = strtok($referer, '?') . '?' . $newQuery;

        return $referer ? $this->redirect($newReferer) : $this->redirectToRoute('homepage');
    }
}
