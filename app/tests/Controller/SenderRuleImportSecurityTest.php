<?php

namespace App\Tests\Controller;

use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SenderRuleImportSecurityTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testUserCannotAccessSenderRuleImport(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->user()->create();
        $client->loginUser($user);

        $client->request(Request::METHOD_POST, '/rules/admin/import/W');

        self::assertResponseStatusCodeSame(403);
    }
}
