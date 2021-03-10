<?php

namespace App\Controller;

use App\Entity\Policy;
use App\Form\PolicyType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
/**
 * @Route("/policy")
 */
class PolicyController extends AbstractController
{
  
  
  private $translator;

  public function __construct(TranslatorInterface $translator) {    
    $this->translator = $translator;
  }  
    /**
     * @Route("/", name="policy_index", methods="GET")
     */
    public function index(): Response
    {
        $policies = $this->getDoctrine()
            ->getRepository(Policy::class)
            ->findAll();

        return $this->render('policy/index.html.twig', ['policies' => $policies]);
    }

    /**
     * @Route("/new", name="policy_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $policy = new Policy();
        $form = $this->createForm(PolicyType::class, $policy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($policy);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Generics.flash.addSuccess'));
            return $this->redirectToRoute('policy_index');
        }

        return $this->render('policy/new.html.twig', [
            'policy' => $policy,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="policy_show", methods="GET")
     */
    public function show(Policy $policy): Response
    {
        return $this->render('policy/show.html.twig', ['policy' => $policy]);
    }

    /**
     * @Route("/{id}/edit", name="policy_edit", methods="GET|POST")
     */
    public function edit(Request $request, Policy $policy): Response
    {
        $form = $this->createForm(PolicyType::class, $policy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', $this->translator->trans('Generics.flash.editSuccess'));
            return $this->redirectToRoute('policy_index', ['id' => $policy->getId()]);
        }

        return $this->render('policy/edit.html.twig', [
            'policy' => $policy,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="policy_delete", methods="POST")
     */
    public function delete(Policy $policy): Response
    {
        
        $em = $this->getDoctrine()->getManager();
        $em->remove($policy);
        $em->flush();        

        return new Response('success',  200);
    }
}
