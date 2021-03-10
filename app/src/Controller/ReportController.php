<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\Msgs;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/report")
 */
class ReportController extends AbstractController {

  private $params;
  private $translator;
  public function __construct(ParameterBagInterface $params, TranslatorInterface $translator) {
    $this->params = $params;
    $this->translator = $translator;
  }

  /**
   * @Route("/", name="report")
   */
  public function index() {
      
    $email = null;
    if (in_array('ROLE_USER', $this->getUser()->getRoles())) {
      $email = stream_get_contents($this->getUser()->getEmail(), -1, 0);
    }
    $end = time();
    $start = strtotime('-1 day', $end);
    $reportMsgs['day'] = $this->getDoctrine()->getManager()->getRepository(Msgs::class)->getAllMessageReceipientForReport($email,$start,$end);

    $start = strtotime('-1 month', $end);
    $reportMsgs['month'] = $this->getDoctrine()->getManager()->getRepository(Msgs::class)->getAllMessageReceipientForReport($email,$start,$end);
    
    
    return $this->render('report/index.html.twig', [
                'controller_name' => 'ReportController',
                'reportMsgs' => $reportMsgs
    ]);
  }


}
