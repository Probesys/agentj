<?php

namespace App\Repository;

use App\Entity\Domain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\FetchMode;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomainRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Domain::class);
    }

  /**
   * GetThe domains owned by a local administrator
   * @param type $userID
   * @return type
   */
    public function getListByUserId($userID = null)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "select domain_id from users_domains";
        if ($userID) {
            $sql .= " where user_id=" . $userID;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $resultArray = $stmt->executeQuery()->fetchFirstColumn();
//        dd(array_values($resultArray));
        if ($resultArray) {
            $dql = $this->createQueryBuilder('d')
              ->where('d in(' . implode(',', $resultArray) . ')');
            $query = $dql->getQuery();
            return $query->getResult();
        } else {
            return null;
        }
    }

  /**
   * Return the default wblist  (mailaddr = @.) for a domain.
   * @param String $domain
   * @param type $defaultMailAddr
   * @return type
   */
    public function getDomainWblist($domain, $defaultMailAddr)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "select w.wb from users u"
            . " inner join wblist w on w.rid=u.id "
            . "where u.email ='" . $domain . "' and w.sid = " . $defaultMailAddr;

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchColumn();
        if ($result) {
            return $result;
        } else {
            return null;
        }
    }



    public function findDomainsWithIMAPConnectors(): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.connectors', 'c')
            ->where('c.type = :imap')
            ->setParameter('imap', 'IMAP')
            ->select('d.id')
            ->getQuery()
            ->getArrayResult();  // Returns array of domain IDs that have IMAP connectors
    }
}
