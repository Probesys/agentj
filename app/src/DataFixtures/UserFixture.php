<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Group;
use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixture extends Fixture
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@blocnormal.fr');
        $user->setReport(true);
        $user->setFullname('user blocnormal');
        $user->setUsername('user@blocnormal.fr');
        $user->setRoles('["ROLE_USER"]');
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['domain' => 'blocnormal.fr']);
        $user->setDomain($domain);
        $user->setPolicy($user->getDomain()->getPolicy());

        $manager->persist($user);

        $user = new User();
        $user->setEmail('user.domain.quota@blocnormal.fr');
        $user->setFullname('userDomainQuota blocnormal');
        $user->setUsername('user.domain.quota@blocnormal.fr');
        $user->setRoles('["ROLE_USER"]');
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['domain' => 'blocnormal.fr']);
        $user->setDomain($domain);
        $user->setPolicy($user->getDomain()->getPolicy());

        $manager->persist($user);

        $user = new User();
        $user->setEmail('user.group1.quota@blocnormal.fr');
        $user->setFullname('userGroup1Quota blocnormal');
        $user->setUsername('user.group1.quota@blocnormal.fr');
        $user->setRoles('["ROLE_USER"]');
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['domain' => 'blocnormal.fr']);
        $user->setDomain($domain);
        $group = $this->em->getRepository(Group::class)->findOneBy(['name' => 'group1']);
        $user->addGroup($group);
        $user->setPolicy($user->getDomain()->getPolicy());

        $manager->persist($user);

        $user = new User();
        $user->setEmail('user.group1.perso.small.quota@blocnormal.fr');
        $user->setFullname('userGroup1PersoSmallQuota blocnormal');
        $user->setUsername('user.group1.perso.small.quota@blocnormal.fr');
        $user->setRoles('["ROLE_USER"]');
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['domain' => 'blocnormal.fr']);
        $user->setDomain($domain);

        $group = $this->em->getRepository(Group::class)->findOneBy(['name' => 'group1']);
        $user->addGroup($group);
        $user->setPolicy($user->getDomain()->getPolicy());
        $user->setQuota([["quota_emails" => 1, "quota_seconds" => 5]]);

        $manager->persist($user);

        $user = new User();
        $user->setEmail('user.group1.perso.large.quota@blocnormal.fr');
        $user->setFullname('userGroup1PersoLargeQuota blocnormal');
        $user->setUsername('user.group1.perso.large.quota@blocnormal.fr');
        $user->setRoles('["ROLE_USER"]');
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['domain' => 'blocnormal.fr']);
        $user->setDomain($domain);
        $group = $this->em->getRepository(Group::class)->findOneBy(['name' => 'group1']);
        $user->addGroup($group);
        $user->setPolicy($user->getDomain()->getPolicy());
        $user->setQuota([["quota_emails" => 10, "quota_seconds" => 5]]);

        $manager->persist($user);

        // A user with 2 aliases
        $mainUser = new User();
        $mainUser->setEmail('user.main@blocnormal.fr');
        $mainUser->setFullname('userMain blocnormal');
        $mainUser->setUsername('user.main@blocnormal.fr');
        $mainUser->setRoles('["ROLE_USER"]');
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['domain' => 'blocnormal.fr']);
        $mainUser->setDomain($domain);
        $mainUser->setPolicy($mainUser->getDomain()->getPolicy());

        $manager->persist($mainUser);

        foreach (['user.alias1@blocnormal.fr', 'user.alias2@blocnormal.fr'] as $aliasEmail) {
            $alias = new User();
            $alias->setEmail($aliasEmail);
            $alias->setFullname('userMain blocnormal');
            $alias->setUsername($aliasEmail);
            $alias->setRoles('["ROLE_USER"]');
            $alias->setDomain($domain);
            $alias->setOriginalUser($mainUser);
            $alias->setPolicy($mainUser->getPolicy());

            $manager->persist($alias);
        }

        $user = new User();
        $user->setEmail('user@laissepasser.fr');
        $user->setFullname('user laissepasser');
        $user->setUsername('user@laissepasser.fr');
        $user->setRoles('["ROLE_USER"]');
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['domain' => 'laissepasser.fr']);
        $user->setDomain($domain);
        $user->setPolicy($user->getDomain()->getPolicy());

        $manager->persist($user);
        $manager->flush();
    }
}
