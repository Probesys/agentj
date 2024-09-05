<?php

namespace App\Controller;

use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use App\Entity\User;
use App\Form\SearchFilterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class SearchController extends AbstractController
{

    private $translator;
    private $em;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em)
    {
        $this->translator = $translator;
        $this->em = $em;
    }

   #[Route(path: '/advanced-search', name: 'advanced_search', methods: ['GET', 'POST'])]
   public function advancedSearch(Request $request, EntityManagerInterface $entityManager): Response
   {
       $form = $this->createForm(SearchFilterType::class);
       $form->handleRequest($request);

       $data = $form->getData();
       $messageType = $data['messageType'] ?? 'incoming';

       $allMessages = $this->em->getRepository(Msgs::class)->advancedSearch($this->getUser(), $messageType);
// dd($allMessages);

       // Initialize active filters
       $activeFilters = [];

       // If form is submitted and valid, set active filters and filter messages
       if ($form->isSubmitted() && $form->isValid()) {
           $data = $form->getData();

           // Track which filters are active
           foreach ($data as $key => $value) {
               if (!empty($value)) {
                   $activeFilters[$key] = true;
               }
           }

           $allMessages = array_filter($allMessages, function ($message) use ($data) {
               if (!empty($data['fromAddr']) && stripos($message['from_addr'], $data['fromAddr']) === false) {
                   return false;
               }
               if (!empty($data['email']) && stripos($message['email'], $data['email']) === false) {
                   return false;
               }
               if (!empty($data['subject']) && stripos($message['subject'], $data['subject']) === false) {
                   return false;
               }
               if (!empty($data['mailId']) && stripos($message['mail_id'], $data['mailId']) === false) {
                   return false;
               }
               if (!empty($data['amavisOutput']) && stripos($message['amavis_output'], $data['amavisOutput']) === false) {
                   return false;
               }
               if (!empty($data['startDate']) && $message['time_iso'] < $data['startDate']->format('Ymd\THis\Z')) {
                   return false;
               }
               if (!empty($data['endDate']) && $message['time_iso'] > $data['endDate']->format('Ymd\THis\Z')) {
                   return false;
               }
               if (!empty($data['size']) && stripos($message['size'], $data['size']) === false) {
                   return false;
              }
                if (!empty($data['host']) && stripos($message['host'], $data['host']) === false) {
                     return false;
                }
              if (!empty($data['replyTo']) && $message['replyTo'] !== $data['replyTo']) {
                   return false;
              }
              return true;
           });
       }

       // Pagination logic
       $page = $request->query->getInt('page', 1);
       $limit = 50;
       $totalMessages = count($allMessages);
       $offset = ($page - 1) * $limit;

       $paginatedMessages = array_slice($allMessages, $offset, $limit);

       // Handle AJAX requests
       if ($request->isXmlHttpRequest()) {
           return new JsonResponse([
               'content' => $this->renderView('search/_messages.html.twig', [
                   'msgs' => $paginatedMessages,
                   'messageType' => $messageType,
                   'activeFilters' => $activeFilters,
               ]),
           ]);
       }

       // Render the main template
       return $this->render('search/advanced_search.html.twig', [
           'msgs' => $paginatedMessages,
           'current_page' => $page,
           'total_pages' => ceil($totalMessages / $limit),
           'form' => $form->createView(),
           'messageType' => $messageType,
           'activeFilters' => $activeFilters, // Pass the activeFilters to the main view
       ]);
   }


}