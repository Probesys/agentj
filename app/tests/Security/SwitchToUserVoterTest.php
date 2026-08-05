<?php

namespace App\Tests\Security;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SwitchToUserVoterTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testSuperAdminCanSwitchToAnyMailbox(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $userA = UserFactory::new()->user()->create();
        $client->loginUser($superAdmin);

        $client->request(Request::METHOD_GET, '/', ['_switch_user' => $userA->getUsername()]);
        $this->assertResponseRedirects('/', 302);
    }

    public function testAdminCanSwitchToMailboxOfHisDomains(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $user = UserFactory::new()->user($domain)->create();
        $client->loginUser($admin);

        $client->request(Request::METHOD_GET, '/', ['_switch_user' => $user->getUsername()]);

        $this->assertResponseRedirects('/', 302);
    }

    public function testAdminCannotSwitchToMailboxOfOtherDomains(): void
    {
        $client = static::createClient();
        $adminDomain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$adminDomain])->create();
        $user = UserFactory::new()->user($otherDomain)->create();
        $client->loginUser($admin);

        $client->request(Request::METHOD_GET, '/', ['_switch_user' => $user->getUsername()]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCanSwitchToAnotherSharedMailbox(): void
    {
        $client = static::createClient();
        // userA has access to both userB and userC's shared mailboxes.
        $userA = UserFactory::new()->user()->create();
        $userB = UserFactory::new()->user()->create(['sharedWith' => [$userA]]);
        $userC = UserFactory::new()->user()->create(['sharedWith' => [$userA]]);
        $client->loginUser($userA);

        // userA switches to userB's mailbox.
        $client->request(Request::METHOD_GET, '/', ['_switch_user' => $userB->getUsername()]);
        $this->assertResponseRedirects('/', 302);

        // While impersonating userB, userA switches directly to userC's
        // mailbox. userB has no access to userC on their own, but userA
        // (the real, original user behind the impersonation) does, so the
        // switch must be allowed.
        $client->request(Request::METHOD_GET, '/', ['_switch_user' => $userC->getUsername()]);
        $this->assertResponseRedirects('/', 302);
    }

    public function testUserCannotChainSwitchToAMailboxImpersonatedUserHasAccess(): void
    {
        $client = static::createClient();
        $userA = UserFactory::new()->user()->create();
        $userB = UserFactory::new()->user()->create(['sharedWith' => [$userA]]);
        // userC is shared with userB but not userA. This case could be
        // legitimate (being careful with priviledge escalation), but
        // SwitchToUserVoter doesn't have access to the SwitchUserToken and so
        // we cannot support this case. It would probably require a different
        // system.
        $userC = UserFactory::new()->user()->create(['sharedWith' => [$userB]]);
        $client->loginUser($userA);

        $client->request(Request::METHOD_GET, '/', ['_switch_user' => $userB->getUsername()]);
        $this->assertResponseRedirects('/', 302);

        $client->request(Request::METHOD_GET, '/', ['_switch_user' => $userC->getUsername()]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCannotSwitchToAMailboxHeCannotAccess(): void
    {
        $client = static::createClient();
        $userA = UserFactory::new()->user()->create();
        $userB = UserFactory::new()->user()->create(['sharedWith' => []]);
        $client->loginUser($userA);

        $client->request(Request::METHOD_GET, '/', ['_switch_user' => $userB->getUsername()]);

        $this->assertResponseStatusCodeSame(403);
    }
}
