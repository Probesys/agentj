<?php
// src/Controller/OutMsgController.php

namespace App\Controller;

use App\Entity\OutMsg;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OutMsgController extends AbstractController
{
    #[Route(path: '/outmsg/process/{id}', name: 'outmsg_process')]
    public function process(string $id, EntityManagerInterface $entityManager): Response
    {
        // Convert mailId back to binary
        $binaryMailId = hex2bin($id);
        if ($binaryMailId === false) {
            return new Response('Error: Failed to convert mailId to binary', Response::HTTP_BAD_REQUEST);
        }

        // Fetch the OutMsg entity
        $outMsg = $entityManager->getRepository(OutMsg::class)->findOneBy(['mail_id' => $binaryMailId]);
        if (!$outMsg) {
            return new Response('OutMsg not found for mail_id: ' . $id, Response::HTTP_NOT_FOUND);
        }

        // Modify the processed state
        $outMsg->setProcessed(true);
        $entityManager->persist($outMsg);
        $entityManager->flush();

        return new Response('OutMsg processed state set to true for mail_id: ' . $id);
    }

    #[Route(path: '/outmsg/status/{id}', name: 'outmsg_status')]
    public function status(string $id, EntityManagerInterface $entityManager): Response
    {
        // Convert mailId back to binary
        $binaryMailId = hex2bin($id);
        if ($binaryMailId === false) {
            return new Response('Error: Failed to convert mailId to binary', Response::HTTP_BAD_REQUEST);
        }

        // Fetch the OutMsg entity
        $outMsg = $entityManager->getRepository(OutMsg::class)->findOneBy(['mail_id' => $binaryMailId]);
        if (!$outMsg) {
            return new Response('OutMsg not found for mail_id: ' . $id, Response::HTTP_NOT_FOUND);
        }

        return new Response('OutMsg processed state: ' . ($outMsg->isProcessed() ? 'true' : 'false'));
    }
}