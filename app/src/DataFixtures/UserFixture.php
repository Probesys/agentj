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
