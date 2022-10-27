<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\Mailaddr;
use App\Entity\User;
use App\Entity\Wblist;
use Cocur\Slugify\Slugify;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, User::class);
    }

    public function findOneByUid(string $uid): ?User {
        return $this->createQueryBuilder('u')
                        ->join('u.domain', 'd')
                        ->andWhere('u.uid = :uid')
                        ->andWhere('d.active=1')
                        ->setParameter('uid', $uid)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    public function findOneByEmail(string $email): ?User {
        return $this->createQueryBuilder('u')
                        ->join('u.domain', 'd')
                        ->andWhere('u.email = :email')
                        ->andWhere('d.active=1')
                        ->setParameter('email', $email)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Return all users from active domains
     * @param boolean $withAlias
     * @return type
     */
    public function activeUsers($withAlias = false) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT u.id from users u
            inner join domain d on u.domain_id=d.id
            where d.active=1 and u.roles="[\'ROLE_USER\']"';
        if (!$withAlias) {
            $sql .= " and u.original_user_id is null ";
        }

        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * search query
     * @param type $login
     * @return type
     */
    public function search($login) {

        $login = strtolower($login);
        $dql = $this->createQueryBuilder('a')
                ->select('a')
        ;

        if (isset($login) && $login != '') {
            $dql->andwhere('a.email = \'' . $login . '\'');
        }

        return $dql->getQuery();
    }

    /**
     * search query by role
     * @param type $roles
     * @return type
     */
    public function searchByRole(User $user, $role = null) {
        $conn = $this->getEntityManager()->getConnection();

//        $sql = "SELECT usr.id, usr.email, usr.fullname, usr.username ,usr.roles, usr.imaplogin from users usr ";
        $dql = $this->createQueryBuilder('u')
                ->select('u.id, u.email, u.fullname, u.username, u.roles, u.imapLogin')
                ->where('u.originalUser is null');

        if ($role) {
            $dql->andWhere('u.roles = :role');
            $dql->setParameter('role', $role);
        }

//        if (is_array($roles) && count($roles) > 0) {
//            $i = 0;
//
//            foreach ($roles as $role) {
//                if ($i == 0) {
//                    $sql .= " where usr.roles like '%" . $role . "%'";
//                } else {
//                    $sql .= " or usr.roles like '%" . $role . "%'";
//                }
//                $i++;
//            }
//        }
        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            $domainsIds = array_map(function ($entity) {
                return $entity->getId();
            }, $user->getDomains()->toArray());
            $dql->andWhere('u.domain in (' . implode(',', $domainsIds) . ')');
//            $sql .= ' AND usr.domain_id in (' . implode(',', $domainsIds) . ') ';
        }

//        $sql .= ' AND original_user_id IS NULL '; //without alias
//        $stmt = $conn->prepare($sql);
//        $stmt->execute();
//        $dql->getQuery()->execute();

        $result = $dql->getQuery()->execute();
//        dd($result);
//        dd(count($result));
        return $result;
    }

    public function getListAliases(): ?array {
        $dql = $this->createQueryBuilder('u')
                ->join('u.originalUser', 'a');

        $query = $dql->getQuery();
        return $query->getResult();
    }

    /**
     * Return a list of aliases associate to a user
     * @return type
     */
    public function searchAlias(User $user) {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT usr.id, usr.email as alias, u.email as email  from users usr "
                . " LEFT JOIN users u ON usr.original_user_id = u.id "
                . " WHERE usr.original_user_id IS NOT NULL ";
        ;
        //todo finir les droits sur les domaines
        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            $domainsIds = array_map(function ($entity) {
                return $entity->getId();
            }, $user->getDomains()->toArray());

            $sql .= ' AND usr.domain_id in (' . implode(',', $domainsIds) . ') ';
        }
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     *  autocomplete query
     * @param type $q
     * @param type $all
     * @return type
     */
    public function autocomplete($q, $page_limit = 30, $page = null, $allowedomains = null) {
        //    $slugify = new Slugify();
        $dql = $this->createQueryBuilder('u')
                ->select('u.id, u.email')
                ->andWhere(" u.originalUser is null")// filter only email user without alias
                ->andWhere(" u.roles !='[\"ROLE_SUPER_ADMIN\"]'")// filter only email user without alias
                ->andWhere(" u.email NOT LIKE '@%' ")// filter only email user without domain
        ;

        if ($allowedomains) {
            $domainsIds = array_map(function ($domain) {
                return $domain->getId();
            }, $allowedomains);
            $dql->andWhere("u.domain in (" . implode(',', $domainsIds) . ")");
        }

        if ($q) {
            $dql->andWhere("u.email LIKE '%" . $q . "%'");
        }
        $query = $dql->getQuery();
        $query->setMaxResults($page_limit);

        if ($page) {
            $query->setFirstResult(($page - 1) * $page_limit);
        }
        return $query->getResult();
    }

    /**
     * Search users in list of domains
     * @param type $domains
     * @param type $q
     * @param type $page_limit
     * @param type $page
     * @return type
     */
    public function searchInDomains($domain, $q, $page_limit = 30, $page = null) {
        $slugify = new Slugify();
        $slug = $slugify->slugify($q, '-');

        $dql = $this->createQueryBuilder('u')
                ->select('u.id, u.email')
                ->where(" u.username is null")// filter only email user without user admin

        ;

        $dql->andWhere("u.domain = '" . $domain . "'");
        if ($q && strlen($q) >= 3) {
            $dql->andWhere("u.email LIKE '%" . $slug . "%'");
            $query = $dql->getQuery();
            $query->getSQL();
            $query->setMaxResults($page_limit);
            if ($page) {
                $query->setFirstResult(($page - 1) * $page_limit);
            }
            return $query->getResult();
        } else {
            return null;
        }
    }

    /**
     * Update the user policy from group policy
     * @param type $groupId
     * @return type
     */
    public function updatePolicyForGroup($groupId) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = " UPDATE users set policy_id = (SELECT policy_id FROM groups WHERE id = " . $groupId . " ) "
                . " WHERE groups_id =  " . $groupId
        ;
        $stmt = $conn->prepare($sql);
        return $stmt->execute();
    }

    /**
     * Update the user policy from domain policy
     * @param type $groupId
     * @return type
     */
    public function updatePolicyFromGroupToDomain($groupId) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = " UPDATE users  u "
                . "INNER JOIN groups g on g.id=u.groups_id "
                . "INNER JOIN domain d on d.id=u.domain_id "
                . " set u.policy_id =d.policy_id "
        ;
        $stmt = $conn->prepare($sql);
        return $stmt->execute();
    }

    /**
     * create a default wblist entry for the new user based on domain wblist
     * @param type $user
     */
    public function createDefaultWbListFromdomain(User $user) {

        $mailaddr = $this->getEntityManager()->getRepository(Mailaddr::class)->findOneBy((['email' => '@.']));
        $domainWblist = $this->getEntityManager()->getRepository(Domain::class)->getDomainWblist('@' . $user->getDomain(), $mailaddr->getId());
        $userDefaultWbList = $this->getEntityManager()->getRepository(Wblist::class)->findOneby(['rid' => $user, 'sid' => $mailaddr]);
        if (!$userDefaultWbList) {
            $userDefaultWbList = new Wblist($user, $mailaddr);
            $userDefaultWbList->setWb($domainWblist);
            $userDefaultWbList->setPriority(Wblist::WBLIST_PRIORITY_DOMAIN);
            $this->getEntityManager()->persist($userDefaultWbList);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Return the main (hightest priority) group of the user $user
     * @param User $user
     * @return array|null
     */
    public function getMainUserGroup(User $user): ?array {
        $dql = $this->createQueryBuilder('u')
                ->innerJoin('u.groups', 'g')
                ->where('g.active = true')
                ->orderBy('g.priority', 'DESC');
        $query = $dql->getQuery()->setMaxResults(1);
        return $query->getResult();
    }
    


}
