<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use function dd;


class UserService
{

  //  private $params;

    private ParameterBagInterface $params;
    private EntityManagerInterface $em;
    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em)
    {
        $this->params = $params;
        $this->em = $em;
    }

    public function updateUsersPolicyfromDoamin(Domain $domain){
        
        $users = $this->em->getRepository(User::class)->findBy([
            'domain' => $domain,
            'groups' => null
        ]);
        foreach ($users as $user){
            
            /*@var $user User */
            $user->setPolicy($domain->getPolicy());
            $this->em->persist($user);
            $this->em->flush();            
        }
        
        
    }
}
