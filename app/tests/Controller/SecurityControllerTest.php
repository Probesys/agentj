<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SecurityControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testGetLoginRendersCorrectly(): void
    {
        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#loginBtn', 'Sign in');
        $user = $this->getLoggedUser();
        $this->assertNull($user);
    }

    public function testGetLoginRedirectsIfAlreadyConnected(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne();
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/login');

        $this->assertResponseRedirects('/', 302);
        $user = $this->getLoggedUser();
        $this->assertNotNull($user);
    }

    protected function getLoggedUser(): ?User
    {
        /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface */
        $tokenStorage = $this->getContainer()->get('security.token_storage');
        $token = $tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        /** @var ?User */
        $user = $token->getUser();
        return $user;
    }
}
