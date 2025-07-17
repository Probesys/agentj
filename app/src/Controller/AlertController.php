<?php

namespace App\Controller;

use App\Entity\Alert;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class AlertController extends AbstractController
{
    #[Route('/alert/read/{id}', name: 'alert_read')]
    public function read(Alert $alert, EntityManagerInterface $entityManager): Response
    {
        $alert->setIsRead(true);
        $entityManager->flush();

        return $this->redirectToRoute('homepage');
    }

    #[Route('/alert/delete/{id}', name: 'alert_delete')]
    public function delete(Alert $alert, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($alert);
        $entityManager->flush();

        return $this->redirectToRoute('homepage');
    }

    #[Route('/alerts', name: 'alert_index')]
    public function index(EntityManagerInterface $entityManager, UserInterface $user): Response
    {
        $alerts = $entityManager->getRepository(Alert::class)->findBy(['user' => $user]);

        return $this->render('alert/index.html.twig', [
            'alerts' => $alerts,
        ]);
    }
}
