<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route(path: '/admin/config')]
class AdminController extends AbstractController
{


  #[Route(path: '/delete-logo', name: 'delete_logo', methods: 'GET', options: ['expose' => true])]
    public function deleteLogo(): JsonResponse
    {

        if (file_exists($this->getParameter('app.upload_directory') . 'logo.png')) {
            if (unlink($this->getParameter('app.upload_directory') . 'logo.png')) {
                $return['result'] = true;
                return new JsonResponse($return, 200);
            } else {
                $return['result'] = false;
                return new JsonResponse($return, 200);
            }
        }
        $return['result'] = true;
        return new JsonResponse($return, 200);
    }
}
