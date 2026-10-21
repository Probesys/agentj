<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AddressRepository;
use App\Service\RateLimitService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route(path: 'quotas')]
class QuotaController extends AbstractController
{
    #[Route(path: '/user/{id}', name: 'user_quotas', methods: 'GET')]
    public function getUserQuota(
        User $user,
        RateLimitService $rateLimitService,
        AddressRepository $addressRepository,
    ): Response {
        $currentUser = $this->getUser();

        $isSameUser = $currentUser instanceof User
            && $currentUser->getId() === $user->getId();

        $hasDomainAccess = $this->isGranted('DOMAIN_ACCESS', $user);

        if (!$isSameUser && !$hasDomainAccess) {
            throw new AccessDeniedException();
        }

        $userAddress = $addressRepository->findOneBy([
            'email' => $user->getEmail(),
        ]);

        $quotaData = $rateLimitService->getQuotaUsage($user, $userAddress);

        return $this->render('user/quotas.html.twig', [
            'user' => $user,
            'type' => $quotaData['type'],
            'usage' => $quotaData['usage'],
        ]);
    }
}
