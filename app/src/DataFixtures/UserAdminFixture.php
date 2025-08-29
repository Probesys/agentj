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
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['domain' => 'blocnormal.fr']);

        for ($i = 1; $i <= 20; $i++) {
            $admin = new User();
            $admin->setEmail('admin' . $i . '@blocnormal.fr');
            $admin->setFullname('admin' . $i . ' blocnormal');
            $admin->setUsername('admin' . $i . '@blocnormal.fr');
            $admin->setRoles('["ROLE_ADMIN"]');
            $admin->addDomain($domain);
            $admin->setPolicy($domain->getPolicy());

            $manager->persist($admin);
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
