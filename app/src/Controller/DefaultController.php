<?php

namespace App\Controller;

use App\Amavis\ContentType;
use App\Amavis\MessageStatus;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\MsgrcptRepository;
use App\Service\Referrer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    public function __construct(
        private Security $security,
        private Referrer $referrer,
    ) {
    }

    #[Route(path: '/', name: 'homepage')]
    public function index(
        #[Autowire(env: 'bool:FEATURE_FLAG_DISABLE_DASHBOARD')]
        bool $featureFlagDisableDashboard,
    ): Response {
        if ($featureFlagDisableDashboard) {
            return $this->redirect('message');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $showAlertWidget = true;
        if ($user->getDomain() && $user->getDomain()->getSendUserAlerts() == false) {
            $showAlertWidget = false;
        }

        return $this->render('home/index.html.twig', [
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

        return new RedirectResponse($this->referrer->get());
    }

    #[Route('/per_page/{nbItems}', name: 'per_page')]
    public function perPage(int $nbItems, Request $request): Response
    {
        $session = $request->getSession();
        $session->set('perPage', $nbItems);

        $referrer = $this->referrer->get();
        $urlParts = parse_url($referrer);
        parse_str($urlParts['query'] ?? '', $query);
        $query['page'] = 1;
        $newQuery = http_build_query($query);
        $newReferrer = strtok($referrer, '?') . '?' . $newQuery;

        return new RedirectResponse($newReferrer);
    }
}
