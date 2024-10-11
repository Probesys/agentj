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
	$user->setDomain($this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'blocnormal.fr']));
	$user->setPolicy($user->getDomain()->getPolicy());

	$manager->persist($user);

	$user = new User();
	$user->setEmail('userDomainQuota@blocnormal.fr');
	$user->setFullname('userDomainQuota blocnormal');
	$user->setUsername('userDomainQuota@blocnormal.fr');
	$user->setRoles('["ROLE_USER"]');
	$user->setDomain($this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'blocnormal.fr']));
	$user->setPolicy($user->getDomain()->getPolicy());

	$manager->persist($user);

	$user = new User();
	$user->setEmail('userGroup1Quota@blocnormal.fr');
	$user->setFullname('userGroup1Quota blocnormal');
	$user->setUsername('userGroup1Quota@blocnormal.fr');
	$user->setRoles('["ROLE_USER"]');
	$user->setDomain($this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'blocnormal.fr']));
	$user->addGroup($this->em
		->getRepository(Groups::class)
		->findOneBy(['name' => 'group1']));
	$user->setPolicy($user->getDomain()->getPolicy());

	$manager->persist($user);

	$user = new User();
	$user->setEmail('userGroup1PersoSmallQuota@blocnormal.fr');
	$user->setFullname('userGroup1PersoSmallQuota blocnormal');
	$user->setUsername('userGroup1PersoSmallQuota@blocnormal.fr');
	$user->setRoles('["ROLE_USER"]');
	$user->setDomain($this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'blocnormal.fr']));
	$user->addGroup($this->em
		->getRepository(Groups::class)
		->findOneBy(['name' => 'group1']));
	$user->setPolicy($user->getDomain()->getPolicy());
	$user->setQuota([["quota_emails" => 1, "quota_seconds" => 1]]);

	$manager->persist($user);

	$user = new User();
	$user->setEmail('userGroup1PersoLargeQuota@blocnormal.fr');
	$user->setFullname('userGroup1PersoLargeQuota blocnormal');
	$user->setUsername('userGroup1PersoLargeQuota@blocnormal.fr');
	$user->setRoles('["ROLE_USER"]');
	$user->setDomain($this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'blocnormal.fr']));
	$user->addGroup($this->em
		->getRepository(Groups::class)
		->findOneBy(['name' => 'group1']));
	$user->setPolicy($user->getDomain()->getPolicy());
	$user->setQuota([["quota_emails" => 10, "quota_seconds" => 1]]);

	$manager->persist($user);

	$user = new User();
	$user->setEmail('user@laissepasser.fr');
	$user->setFullname('user laissepasser');
	$user->setUsername('user@laissepasser.fr');
	$user->setRoles('["ROLE_USER"]');
	$user->setDomain($this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'laissepasser.fr']));
	$user->setPolicy($user->getDomain()->getPolicy());

	$manager->persist($user);
	$manager->flush();
    }
}
