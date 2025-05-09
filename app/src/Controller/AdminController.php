<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route(path: '/admin/config')]
class AdminController extends AbstractController
{


  #[Route(path: '/delete-logo', name: 'delete_logo', methods: 'GET', options: ['expose' => true])]
    public function deleteLogo(): Response
    {

        if (file_exists($this->getParameter('app.upload_directory') . 'logo.png')) {
            if (unlink($this->getParameter('app.upload_directory') . 'logo.png')) {
                $return['result'] = true;
                return new Response(json_encode($return), 200);
            } else {
                $return['result'] = false;
                return new Response(json_encode($return), 200);
            }
        }
        $return['result'] = true;
        return new Response(json_encode($return), 200);
    }
}
