<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Groups;
use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixture extends Fixture
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function load(ObjectManager $manager): void
    {
	$user = new User();
	$user->setEmail('user@blocnormal.fr');
	$user->setFullname('user blocnormal');
	$user->setUsername('user@blocnormal.fr');
	$user->setRoles('["ROLE_USER"]');
	$domain = $this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'blocnormal.fr']);
	$user->setDomain($domain);
	$user->setPolicy($user->getDomain()->getPolicy());

	$manager->persist($user);

	$user = new User();
	$user->setEmail('user.domain.quota@blocnormal.fr');
	$user->setFullname('userDomainQuota blocnormal');
	$user->setUsername('user.domain.quota@blocnormal.fr');
	$user->setRoles('["ROLE_USER"]');
	$domain = $this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'blocnormal.fr']);
	$user->setDomain($domain);
	$user->setPolicy($user->getDomain()->getPolicy());

	$manager->persist($user);

	$user = new User();
	$user->setEmail('user.group1.quota@blocnormal.fr');
	$user->setFullname('userGroup1Quota blocnormal');
	$user->setUsername('user.group1.quota@blocnormal.fr');
	$user->setRoles('["ROLE_USER"]');
	$domain = $this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'blocnormal.fr']);
	$user->setDomain($domain);
	$group = $this->em
		->getRepository(Groups::class)
		->findOneBy(['name' => 'group1']);
	$user->addGroup($group);
	$user->setPolicy($user->getDomain()->getPolicy());

	$manager->persist($user);

	$user = new User();
	$user->setEmail('user.group1.perso.small.quota@blocnormal.fr');
	$user->setFullname('userGroup1PersoSmallQuota blocnormal');
	$user->setUsername('user.group1.perso.small.quota@blocnormal.fr');
	$user->setRoles('["ROLE_USER"]');
	$domain = $this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'blocnormal.fr']);
	$user->setDomain($domain);

	$group = $this->em
		->getRepository(Groups::class)
		->findOneBy(['name' => 'group1']);
	$user->addGroup($group);
	$user->setPolicy($user->getDomain()->getPolicy());
	$user->setQuota([["quota_emails" => 1, "quota_seconds" => 5]]);

	$manager->persist($user);

	$user = new User();
	$user->setEmail('user.group1.perso.large.quota@blocnormal.fr');
	$user->setFullname('userGroup1PersoLargeQuota blocnormal');
	$user->setUsername('user.group1.perso.large.quota@blocnormal.fr');
	$user->setRoles('["ROLE_USER"]');
	$domain = $this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'blocnormal.fr']);
	$user->setDomain($domain);
	$group = $this->em
		->getRepository(Groups::class)
		->findOneBy(['name' => 'group1']);
	$user->addGroup($group);
	$user->setPolicy($user->getDomain()->getPolicy());
	$user->setQuota([["quota_emails" => 10, "quota_seconds" => 5]]);

	$manager->persist($user);

	$user = new User();
	$user->setEmail('user@laissepasser.fr');
	$user->setFullname('user laissepasser');
	$user->setUsername('user@laissepasser.fr');
	$user->setRoles('["ROLE_USER"]');
	$domain = $this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'laissepasser.fr']);
	$user->setDomain($domain);
	$user->setPolicy($user->getDomain()->getPolicy());

	$manager->persist($user);
	$manager->flush();
    }
}
