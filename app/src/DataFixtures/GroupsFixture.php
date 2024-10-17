<?php

namespace App\DataFixtures;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\User;
use App\Entity\Policy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GroupsFixture extends Fixture
{
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function load(ObjectManager $manager): void
    {
		$group = new Groups();
		$group->setName('group1');
		$group->setDomain($this->em
			->getRepository(Domain::class)
			->findOneBy(['domain' => 'blocnormal.fr']));
		$group->setPolicy($this->em
			->getRepository(Policy::class)
			->findOneBy(['policyName' => 'Normale']));
		$group->setWb('0');
		$group->setSlug('group1');
		$group->setOverrideUser(false);
		$group->setQuota([["quota_emails" => 2, "quota_seconds" => 1]]);

		$manager->persist($group);
		$manager->flush();

    }
}
