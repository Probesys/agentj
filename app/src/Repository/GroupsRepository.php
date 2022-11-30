<?php

namespace App\Repository;

use App\Entity\Groups;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Captcha|null find($id, $lockMode = null, $lockVersion = null)
 * @method Captcha|null findOneBy(array $criteria, array $orderBy = null)
 * @method Captcha[]    findAll()
 * @method Captcha[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupsRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Groups::class);
    }

    public function findOneByUid(string $uid): ?Groups {
        return $this->createQueryBuilder('g')
                        ->where('g.uid = :uid')
                        ->setParameter('uid', $uid)
                        ->getQuery()
                        ->getOneOrNullResult();
    }
    
    public function findOneByLdapDN(string $dn): ?Groups {
        return $this->createQueryBuilder('g')
                        ->where('g.ldapDN = :ldapDN')
                        ->setParameter('ldapDN', $dn)
                        ->getQuery()
                        ->getOneOrNullResult();
    }    

    /**
     * Return the groups associated to one or more domains
     * @param type $domains
     * @return type
     */
    public function findByDomain($domains) {
        $dql = $this->createQueryBuilder('g');

        if (is_array($domains->toArray())) {
            $domainsID = array_map(function ($entity) {
                return $entity->getId();
            }, $domains->toArray());
        } else {
            $domainsID = [];
        }

        if ($domainsID) {
            $dql->where('g.domain in (' . implode(',', $domainsID) . ')');
        }
        $query = $dql->getQuery();

        return $query->getResult();
    }

    /**
     * Return the main (hightest priority) group of the user $user
     * @param User $user
     * @return Groups|null
     */
    public function getMainUserGroup(User $user): ?Groups {
        $dql = $this->createQueryBuilder('g')
                ->innerJoin('g.users', 'u')
                ->where('g.active = true')
                ->andWhere('u.id = :user')
                ->orderBy('g.priority', 'DESC')
                ->setParameter('user', $user);
        $query = $dql->getQuery()->setMaxResults(1);
        return $query->getOneOrNullResult();
    }

    // /**
    //  * @return Captcha[] Returns an array of Captcha objects
    //  */
    /*
      public function findByExampleField($value)
      {
      return $this->createQueryBuilder('c')
      ->andWhere('c.exampleField = :val')
      ->setParameter('val', $value)
      ->orderBy('c.id', 'ASC')
      ->setMaxResults(10)
      ->getQuery()
      ->getResult()
      ;
      }
     */

    /*
      public function findOneBySomeField($value): ?Captcha
      {
      return $this->createQueryBuilder('c')
      ->andWhere('c.exampleField = :val')
      ->setParameter('val', $value)
      ->getQuery()
      ->getOneOrNullResult()
      ;
      }
     */
}
