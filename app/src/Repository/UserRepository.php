<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\Mailaddr;
use App\Entity\User;
use App\Entity\Wblist;
use Cocur\Slugify\Slugify;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository {

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, User::class);
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
    $stmt->execute();
    return $stmt->fetchAll();
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
    $query = $this->_em->createQuery($dql);

    return $query;
  }

  /**
   * search query by role
   * @param type $roles
   * @return type
   */
  public function searchByRoles(User $user, $roles) {
    $conn = $this->getEntityManager()->getConnection();

    $sql = "SELECT usr.id, usr.email, usr.fullname, usr.username ,usr.roles, g.name as groups,usr.imaplogin from users usr "
            . " LEFT JOIN groups g ON usr.groups_id = g.id ";
    if (is_array($roles) && count($roles) > 0) {

      $len = count($roles);
      $i = 0;

      foreach ($roles as $role) {
        if ($i == 0) {
          $sql .= " where usr.roles like '%" . $role . "%'";
        } else {
          $sql .= " or usr.roles like '%" . $role . "%'";
        }
        $i++;
      }
    }
    if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
      $domainsIds = array_map(function ($entity) {
        return $entity->getId();
      }, $user->getDomains()->toArray());

      $sql .= ' AND usr.domain_id in (' . implode($domainsIds, ',') . ') ';
    }
    $sql .= ' AND original_user_id IS NULL '; //without alias
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll();
  }

  /**
   * Return a list of aliases associate to a user
   * @param User $user
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

      $sql .= ' AND usr.domain_id in (' . implode($domainsIds, ',') . ') ';
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll();
  }

  /**
   *  autocomplete query
   * @param type $q
   * @param type $all
   * @return type
   */
  public function autocomplete($q, $page_limit = 30, $page = null, $allowedomains = null) {
    $slugify = new Slugify();
    $dql = $this->createQueryBuilder('u')
            ->select('u.id, u.email')
            ->andWhere(" u.originalUser is null")// filter only email user without alias
            ->andWhere(" u.roles !='[\"ROLE_SUPER_ADMIN\"]'")// filter only email user without alias
            ->andWhere(" u.email NOT LIKE '@%' ")// filter only email user without domain
    ;

    if ($allowedomains) {
      $domainsIds = array_map(function($domain) {
        return $domain->getId();
      }, $allowedomains);
      $dql->andWhere("u.domain in (" . implode(',', $domainsIds) . ")");
    }

    if ($q) {
      $dql->andWhere("u.email LIKE '%" . $q . "%'");
    }
    $query = $this->_em->createQuery($dql);
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
      $query = $this->_em->createQuery($dql);
      $sql = $query->getSQL();
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
   * Update the group_id for user alaises
   * @param type $groupId
   * @return type
   */
  public function updateAliasGroupsFromUser(User $originalUser) {
    if ($originalUser) {
      $groupID = $originalUser->getGroups() ? $originalUser->getGroups()->getId() : 'null';
      $conn = $this->getEntityManager()->getConnection();
      $sql = " UPDATE users  u  set u.groups_id =" . $groupID . " WHERE u.original_user_id = " . $originalUser->getId();
      $stmt = $conn->prepare($sql);
      return $stmt->execute();
    }
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

}