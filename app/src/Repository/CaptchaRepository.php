<?php

namespace App\Repository;

use App\Entity\Captcha;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Captcha|null find($id, $lockMode = null, $lockVersion = null)
 * @method Captcha|null findOneBy(array $criteria, array $orderBy = null)
 * @method Captcha[]    findAll()
 * @method Captcha[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CaptchaRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Captcha::class);
    }

   
}
