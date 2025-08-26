<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Groups;
use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\DataFixtures\UserFixture;
use App\DataFixtures\GroupsFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class UserAdminFixture extends Fixture implements DependentFixtureInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $alias = new User();
            $alias->setEmail('admin' . $i . '@blocnormal.fr');
            $alias->setFullname('admin' . $i . ' blocnormal');
            $alias->setUsername('admin' . $i . '@blocnormal.fr');
            $alias->setRoles('["ROLE_ADMIN"]');
            $domain = $this->em->getRepository(Domain::class)->findOneBy(['domain' => 'blocnormal.fr']);
            $alias->addDomain($domain);
            $alias->setPolicy($domain->getPolicy());

            $manager->persist($alias);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            DomainFixture::class,
            GroupsFixture::class,
            UserFixture::class,
        ];
    }
}
