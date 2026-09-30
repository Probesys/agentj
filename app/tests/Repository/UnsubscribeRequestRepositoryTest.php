<?php

namespace App\Tests\Repository;

use App\Entity\Address;
use App\Entity\MessageRecipient;
use App\Entity\UnsubscribeRequest;
use App\Repository\UnsubscribeRequestRepository;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UnsubscribeRequestFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UnsubscribeRequestRepositoryTest extends KernelTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;

    private UnsubscribeRequestRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(UnsubscribeRequestRepository::class);
    }

    public function testFindLastForListReturnsTheRequestOfTheAddressForTheList(): void
    {
        [$addrS, $addrR] = $this->setupSenderAndRecipient();
        $messageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $request = $this->createRequest($messageRecipient, 'news.example.com');

        $result = $this->repository->findLastForList($addrR, 'news.example.com');

        self::assertNotNull($result);
        self::assertSame($request->getId(), $result->getId());
    }

    public function testFindLastForListReturnsNullForAnotherList(): void
    {
        [$addrS, $addrR] = $this->setupSenderAndRecipient();
        $messageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $this->createRequest($messageRecipient, 'news.example.com');

        $result = $this->repository->findLastForList($addrR, 'other.example.com');

        self::assertNull($result);
    }

    public function testFindLastForListReturnsNullForAnotherAddress(): void
    {
        [$addrS, $addrR] = $this->setupSenderAndRecipient();
        [, $otherAddrR] = $this->setupSenderAndRecipient();
        $messageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $this->createRequest($messageRecipient, 'news.example.com');

        $result = $this->repository->findLastForList($otherAddrR, 'news.example.com');

        self::assertNull($result);
    }

    public function testFindLastForListReturnsTheLastRequest(): void
    {
        [$addrS, $addrR] = $this->setupSenderAndRecipient();
        $messageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $this->createRequest($messageRecipient, 'news.example.com', UnsubscribeRequest::STATUS_FAILED);
        $lastRequest = $this->createRequest($messageRecipient, 'news.example.com');

        $result = $this->repository->findLastForList($addrR, 'news.example.com');

        self::assertNotNull($result);
        self::assertSame($lastRequest->getId(), $result->getId());
        self::assertSame(UnsubscribeRequest::STATUS_SENT, $result->getStatus());
    }

    public function testFindLastForMessageRecipientOnlyReturnsRequestOfTheMessage(): void
    {
        [$addrS, $addrR] = $this->setupSenderAndRecipient();
        $firstMessageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $secondMessageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $request = $this->createRequest($firstMessageRecipient, null);

        $resultForFirst = $this->repository->findLastForMessageRecipient($firstMessageRecipient);
        $resultForSecond = $this->repository->findLastForMessageRecipient($secondMessageRecipient);

        self::assertNotNull($resultForFirst);
        self::assertSame($request->getId(), $resultForFirst->getId());
        self::assertNull($resultForSecond);
    }

    public function testTruncateOlderDeletesOnlyOldRequests(): void
    {
        [$addrS, $addrR] = $this->setupSenderAndRecipient();
        $messageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $oldRequest = $this->createRequest(
            $messageRecipient,
            'old.example.com',
            createdAt: new \DateTimeImmutable('-91 days'),
        );
        $recentRequest = $this->createRequest($messageRecipient, 'recent.example.com');

        $deletedCount = $this->repository->truncateOlder(90);

        self::assertSame(1, $deletedCount);
        self::assertSame(0, UnsubscribeRequestFactory::count(['id' => $oldRequest->getId()]));
        self::assertSame(1, UnsubscribeRequestFactory::count(['id' => $recentRequest->getId()]));
    }

    /**
     * @return array<int, Address> The sender and recipient addresses
     */
    private function setupSenderAndRecipient(): array
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();

        return $this->setupAddresses($sender, $recipient);
    }

    private function setupMessageRecipient(Address $sender, Address $recipient): MessageRecipient
    {
        $message = $this->setupMail($sender, [$recipient]);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        return $messageRecipient;
    }

    private function createRequest(
        MessageRecipient $messageRecipient,
        ?string $listId,
        string $status = UnsubscribeRequest::STATUS_SENT,
        ?\DateTimeImmutable $createdAt = null,
    ): UnsubscribeRequest {
        return UnsubscribeRequestFactory::new()->forMessageRecipient($messageRecipient)->create([
            'listId' => $listId,
            'status' => $status,
            'createdAt' => $createdAt ?? new \DateTimeImmutable(),
        ]);
    }
}
