<?php

namespace App\DataFixtures;

use App\Entity\Domain;
use App\Entity\DomainRelay;
use App\Entity\User;
use App\Entity\Policy;
use App\Entity\Mailaddr;
use App\Entity\WbList;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DomainFixture extends Fixture
{
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function load(ObjectManager $manager): void
    {
	$this->manager = $manager;

	//for all domain @.
	$mailaddr = new Mailaddr();
	$mailaddr->setPriority(0); // priority for domain is 0
	$mailaddr->setEmail('@.');
	$manager->persist($mailaddr);

	$blocnormal = new Domain();
	$blocnormal->setDomain('blocnormal.fr');
	$blocnormal->setMailAuthenticationSender('will@blocnormal.fr');
	$blocnormal->setPolicy($this->em
		->getRepository(Policy::class)
		->findOneBy(['policyName' => 'Normale']));
	$blocnormal->setQuota([["quota_emails" => 1, "quota_seconds" => 1]]);
	foreach(['172.28.2.5', '172.28.2.1'] as $ip) {
		$authorized_sender = new DomainRelay();
		$authorized_sender->setIpAddress($ip);
		$blocnormal->addDomainRelay($authorized_sender);
	}

	$dkim = $blocnormal->getDomainKeys();
	$dkim->setPrivateKey('-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQChfCZNWSs0p0lA5hVfPYgu38Iag3DQrY3JICLstI63rne3dXP0eN/qdzehbIR2IhEwJ1/8RV/WMRp7Nb2IV4Q7V0Hra+pnmjSiUc+nTgBdxiRQpi8DwevN1Ogcu8nr2XWUY3kYrn0STZTwfEaTf3OFtKJRNy7Ow1ZJrsLii1/c6VWrcsRbaD6yXQJwdacTVLEpUC1L/ch/hetuBm3UNjREJSCZCuSDnDkbA6j6z6dJXWztedTw35Zsb4E1A5sBqBHImwt1lYp9tKJsJ7haDrOOeYSsf8B9QtNpMfH1vEGOCll2HY3lYCKUbJLIoP67jXM5i1+nrj3fOHqx/4LTogCzAgMBAAECggEASNEitF4xDV0huxIFMR0d+4UKkcoTZQXdmYPUO8hvUoRpl2BvGR4oWiHIBBJa6KoT9hLLRYZC4OLjfguNm51bEycVooLXAECY21jouhiCMcbXOUa2jIs1OWt3/vzu4Fr+mhsA0BBedZJmRsrDSF+ASBpb1yN7B+EtV6xmVKFkaMhXauUIw8xtnT+RrFrffSxxKjEftC2LtfmpBQSLURY1Q2Fe9zyEj3zTIb1fe8Mm+mPfbFViPoMAqPadKlOPywcvcNhBYH71tBj20EJ4B08zIcLCVRvQU69gDLpqZ/fH/hl+aU6BBs6131yOIWgKfoq1g2dvgz4fX3gn++FdQPGGgQKBgQDLxw5rUvyaF0kq7Nn4wU3dzMMebRlUu9kdSyzDic1kEalXedK20Ai+1Gqfdb43mVJn6XcoNi2lp5y+eYDMiOs102iWZq2mqM2VIWnZtcqLoi8LlpMDcWgoH5ehr8UMD5iJyDmKSgJJoPp73BUFB/mWJ0bJktiXKcH+/ROKYFI0FwKBgQDK3nZ5HMAKQbPwFQhhveUo0nuCJH637TWdf3VQSdeAjlHoO6wWT+iOqbHWKFHc7aCgP9hpwA4eZ3r4x5Ap0sTyNA5mtM+Uqat/JNKWnzbCdQqKnr37bAweOSgubvkmVdIrr/8XPhbF1yZLnGF1sbsNmUs2xL4Q5lMTKjzV6xNNxQKBgGzzoOIBHM3GZXht6pz1vYw5TpmV+1UymoLvDp/9rbMzPjdnCyJzWDmvmNJpQaq4bzbfvLXjQcSwOT4d+J9jFV9SCTlg7LeOyVxS4SVl7UV7EWxAtZnBqM7LFWd9cv1f2U7RnvIxX8e+Ki7PHO3ztZ5yoYZk3Sj1SqPq6+ewWENBAoGAbHja3BNVU6ah8cMtQiXpPBSfWYzt/KZnPpmCPrXc9q4ieYw+jYeYj3+IyTux2fFtK4I30wOQCQ3HoPID0XkTOXZAJQaU36aBPnCP8V2cSAmQ42HRr3esWxSwuXM44RiOUjG+sczPHGXX4iHxsp6fp7vJjbVQ83RUAzFYagFRxwkCgYBfDhEEbv9F2s6eScFpxsf7CsZQfwSrlaqwO5z82VM21MyaIC3KfN0Jz6EkmTZJBt4jeyRANtYTxUoSvkf8+1TYbymW57ksCzWw10+b0w15effdhkg+yza8Y5Mwmw5bd+H0PQhkqmd9VNljiWO8wvGZWUE5x3cVGKauQFsHI4FeZg==
-----END PRIVATE KEY-----');
	$dkim->setPublicKey('-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAoXwmTVkrNKdJQOYVXz2ILt/CGoNw0K2NySAi7LSOt653t3Vz9Hjf6nc3oWyEdiIRMCdf/EVf1jEaezW9iFeEO1dB62vqZ5o0olHPp04AXcYkUKYvA8HrzdToHLvJ69l1lGN5GK59Ek2U8HxGk39zhbSiUTcuzsNWSa7C4otf3OlVq3LEW2g+sl0CcHWnE1SxKVAtS/3If4XrbgZt1DY0RCUgmQrkg5w5GwOo+s+nSV1s7XnU8N+WbG+BNQObAagRyJsLdZWKfbSibCe4Wg6zjnmErH/AfULTaTHx9bxBjgpZdh2N5WAilGySyKD+u41zOYtfp6493zh6sf+C06IAswIDAQAB
-----END PUBLIC KEY-----');

	$this->fillDomainCommon($blocnormal, $mailaddr, '0');

	$laissepasser = new Domain();
	$laissepasser->setDomain('laissepasser.fr');
	$laissepasser->setPolicy($this->em
		->getRepository(Policy::class)
		->findOneBy(['policyName' => 'Pas de censure']));
	$laissepasser->setQuota([["quota_emails" => 3, "quota_seconds" => 1]]);
	$dkim = $laissepasser->getDomainKeys();
	$dkim->setPrivateKey('-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQChfCZNWSs0p0lA5hVfPYgu38Iag3DQrY3JICLstI63rne3dXP0eN/qdzehbIR2IhEwJ1/8RV/WMRp7Nb2IV4Q7V0Hra+pnmjSiUc+nTgBdxiRQpi8DwevN1Ogcu8nr2XWUY3kYrn0STZTwfEaTf3OFtKJRNy7Ow1ZJrsLii1/c6VWrcsRbaD6yXQJwdacTVLEpUC1L/ch/hetuBm3UNjREJSCZCuSDnDkbA6j6z6dJXWztedTw35Zsb4E1A5sBqBHImwt1lYp9tKJsJ7haDrOOeYSsf8B9QtNpMfH1vEGOCll2HY3lYCKUbJLIoP67jXM5i1+nrj3fOHqx/4LTogCzAgMBAAECggEASNEitF4xDV0huxIFMR0d+4UKkcoTZQXdmYPUO8hvUoRpl2BvGR4oWiHIBBJa6KoT9hLLRYZC4OLjfguNm51bEycVooLXAECY21jouhiCMcbXOUa2jIs1OWt3/vzu4Fr+mhsA0BBedZJmRsrDSF+ASBpb1yN7B+EtV6xmVKFkaMhXauUIw8xtnT+RrFrffSxxKjEftC2LtfmpBQSLURY1Q2Fe9zyEj3zTIb1fe8Mm+mPfbFViPoMAqPadKlOPywcvcNhBYH71tBj20EJ4B08zIcLCVRvQU69gDLpqZ/fH/hl+aU6BBs6131yOIWgKfoq1g2dvgz4fX3gn++FdQPGGgQKBgQDLxw5rUvyaF0kq7Nn4wU3dzMMebRlUu9kdSyzDic1kEalXedK20Ai+1Gqfdb43mVJn6XcoNi2lp5y+eYDMiOs102iWZq2mqM2VIWnZtcqLoi8LlpMDcWgoH5ehr8UMD5iJyDmKSgJJoPp73BUFB/mWJ0bJktiXKcH+/ROKYFI0FwKBgQDK3nZ5HMAKQbPwFQhhveUo0nuCJH637TWdf3VQSdeAjlHoO6wWT+iOqbHWKFHc7aCgP9hpwA4eZ3r4x5Ap0sTyNA5mtM+Uqat/JNKWnzbCdQqKnr37bAweOSgubvkmVdIrr/8XPhbF1yZLnGF1sbsNmUs2xL4Q5lMTKjzV6xNNxQKBgGzzoOIBHM3GZXht6pz1vYw5TpmV+1UymoLvDp/9rbMzPjdnCyJzWDmvmNJpQaq4bzbfvLXjQcSwOT4d+J9jFV9SCTlg7LeOyVxS4SVl7UV7EWxAtZnBqM7LFWd9cv1f2U7RnvIxX8e+Ki7PHO3ztZ5yoYZk3Sj1SqPq6+ewWENBAoGAbHja3BNVU6ah8cMtQiXpPBSfWYzt/KZnPpmCPrXc9q4ieYw+jYeYj3+IyTux2fFtK4I30wOQCQ3HoPID0XkTOXZAJQaU36aBPnCP8V2cSAmQ42HRr3esWxSwuXM44RiOUjG+sczPHGXX4iHxsp6fp7vJjbVQ83RUAzFYagFRxwkCgYBfDhEEbv9F2s6eScFpxsf7CsZQfwSrlaqwO5z82VM21MyaIC3KfN0Jz6EkmTZJBt4jeyRANtYTxUoSvkf8+1TYbymW57ksCzWw10+b0w15effdhkg+yza8Y5Mwmw5bd+H0PQhkqmd9VNljiWO8wvGZWUE5x3cVGKauQFsHI4FeZg==
-----END PRIVATE KEY-----');
	$dkim->setPublicKey('-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAoXwmTVkrNKdJQOYVXz2ILt/CGoNw0K2NySAi7LSOt653t3Vz9Hjf6nc3oWyEdiIRMCdf/EVf1jEaezW9iFeEO1dB62vqZ5o0olHPp04AXcYkUKYvA8HrzdToHLvJ69l1lGN5GK59Ek2U8HxGk39zhbSiUTcuzsNWSa7C4otf3OlVq3LEW2g+sl0CcHWnE1SxKVAtS/3If4XrbgZt1DY0RCUgmQrkg5w5GwOo+s+nSV1s7XnU8N+WbG+BNQObAagRyJsLdZWKfbSibCe4Wg6zjnmErH/AfULTaTHx9bxBjgpZdh2N5WAilGySyKD+u41zOYtfp6493zh6sf+C06IAswIDAQAB
-----END PUBLIC KEY-----');

	$this->fillDomainCommon($laissepasser, $mailaddr, 'W');

	$manager->persist($blocnormal);
	$manager->persist($laissepasser);
	$manager->flush();
    }

    public function fillDomainCommon(Domain $domain, Mailaddr $mailaddr, string $wb_list) {
	$domain->setLevel(0.5);
	$domain->setSrvSmtp('smtp.test');
	$domain->setSmtpPort(25);
	$domain->setActive(true);
	$domain->setTransport("smtp:[" . $domain->getSrvSmtp() . "]:". $domain->getSmtpPort());

	// global user for a fallback policy ?
	$user = new User();
	$user->setEmail('@' . $domain->getDomain());
	$user->setFullname('Domaine ' . $domain->getDomain());
	$user->setDomain($domain);
	$user->setPriority(2);
	$user->setPolicy($domain->getPolicy());
	$this->manager->persist($user);

	// domain wblist for fallback ?
	$wblist = new Wblist($user, $mailaddr);
	$wblist->setWb($wb_list);
	$wblist->setPriority(Wblist::WBLIST_PRIORITY_DOMAIN);
	$this->manager->persist($wblist);
	$dkim = $domain->getDomainKeys();
	$dkim->setDomainName($domain->getDomain());
	$dkim->setSelector('agentj');
    }
}
