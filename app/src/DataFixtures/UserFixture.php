<?php

namespace App\DataFixtures;

use App\Entity\User;
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
	# user@blocnormal.fr : domain quota
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

	# unlimited@blocnormal.fr : large quota
	$user = new User();
	$user->setEmail('unlimited@blocnormal.fr');
	$user->setFullname('unlimited blocnormal');
	$user->setUsername('unlimited@blocnormal.fr');
	$user->setRoles('["ROLE_USER"]');
	$user->setDomain($this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'blocnormal.fr']));
	$user->setPolicy($user->getDomain()->getPolicy());
	$user->setQuota([["quota_emails" => 1000, "quota_seconds" => 1]]);
	$manager->persist($user);

	# limited@blocnormal.fr : large quota
	$user = new User();
	$user->setEmail('limited@blocnormal.fr');
	$user->setFullname('limited blocnormal');
	$user->setUsername('limited@blocnormal.fr');
	$user->setRoles('["ROLE_USER"]');
	$user->setDomain($this->em
		->getRepository(Domain::class)
		->findOneBy(['domain' => 'blocnormal.fr']));
	$user->setPolicy($user->getDomain()->getPolicy());
	$user->setQuota([["quota_emails" => 1, "quota_seconds" => 1]]);
	$manager->persist($user);

	# user@laissepasser.fr : no quota
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
