<?php

namespace App\Controller;

use App\Entity\Msgs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/report")
 */
class ReportController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    /**
   * @Route("/", name="report")
   */
    public function index()
    {

        $email = null;
        if (in_array('ROLE_USER', $this->getUser()->getRoles())) {
            $email = stream_get_contents($this->getUser()->getEmail(), -1, 0);
        }
        $end = time();
        $start = strtotime('-1 day', $end);
        $reportMsgs['day'] = $this->em->getRepository(Msgs::class)->getAllMessageReceipientForReport($email, $start, $end);

        $start = strtotime('-1 month', $end);
        $reportMsgs['month'] = $this->em->getRepository(Msgs::class)->getAllMessageReceipientForReport($email, $start, $end);


        return $this->render('report/index.html.twig', [
                'controller_name' => 'ReportController',
                'reportMsgs' => $reportMsgs
        ]);
    }
}
