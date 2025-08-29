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

class AliasFixture extends Fixture implements DependentFixtureInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function load(ObjectManager $manager): void
    {
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['domain' => 'blocnormal.fr']);
        $originalUser = $this->em->getRepository(User::class)->findOneBy(['email' => 'user@blocnormal.fr']);

        for ($i = 1; $i <= 20; $i++) {
            $alias = new User();
            $alias->setEmail('alias' . $i . '@blocnormal.fr');
            $alias->setFullname('alias' . $i . ' blocnormal');
            $alias->setUsername('alias' . $i . '@blocnormal.fr');
            $alias->setRoles('["ROLE_USER"]');
            $alias->setOriginalUser($originalUser);
            $alias->setDomain($domain);
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
