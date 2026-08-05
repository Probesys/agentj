<?php

namespace App\DataFixtures;

use App\Entity\Domain;
use App\Entity\Group;
use App\Entity\Policy;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class GroupFixture extends Fixture
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function load(ObjectManager $manager): void
    {
        $group = new Group();
        $group->setName('group1');
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['domain' => 'blocnormal.fr']);
        $group->setDomain($domain);
        $normalPolicy = $this->em->getRepository(Policy::class)->findOneBy(['policyName' => 'Normale']);
        $group->setPolicy($normalPolicy);
        $group->setWbRule('none');
        $group->setSlug('group1');
        $group->setOverrideUser(false);
        $group->setQuota([["quota_emails" => 2, "quota_seconds" => 5]]);

        $manager->persist($group);
        $manager->flush();
    }
}
