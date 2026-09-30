<?php

namespace App\Tests\Repository;

use App\Entity\Address;
use App\Entity\MessageRecipient;
use App\Entity\UnsubscribeRequest;
use App\Model\ListUnsubscribe;
use App\Repository\UnsubscribeRequestRepository;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UnsubscribeRequestRepositoryTest extends KernelTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;

    private UnsubscribeRequestRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(UnsubscribeRequestRepository::class);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRequestIsFoundForAnotherMessageOfTheSameList(): void
    {
        [$addrS, $addrR] = $this->setupSenderAndRecipient();
        $firstMessageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $secondMessageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $request = $this->createRequest($firstMessageRecipient, 'news.example.com');

        $result = $this->repository->findLastForMessageRecipient($secondMessageRecipient, 'news.example.com');

        self::assertNotNull($result);
        self::assertSame($request->getId(), $result->getId());
    }

    public function testRequestIsNotFoundForAnotherList(): void
    {
        [$addrS, $addrR] = $this->setupSenderAndRecipient();
        $firstMessageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $secondMessageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $this->createRequest($firstMessageRecipient, 'news.example.com');

        $result = $this->repository->findLastForMessageRecipient($secondMessageRecipient, 'other.example.com');

        self::assertNull($result);
    }

    public function testRequestIsNotFoundForAnotherAddress(): void
    {
        [$addrS, $addrR] = $this->setupSenderAndRecipient();
        [, $otherAddrR] = $this->setupSenderAndRecipient();
        $messageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $otherMessageRecipient = $this->setupMessageRecipient($addrS, $otherAddrR);
        $this->createRequest($messageRecipient, 'news.example.com');

        $result = $this->repository->findLastForMessageRecipient($otherMessageRecipient, 'news.example.com');

        self::assertNull($result);
    }

    public function testRequestWithoutListIdIsOnlyFoundForItsMessage(): void
    {
        [$addrS, $addrR] = $this->setupSenderAndRecipient();
        $firstMessageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $secondMessageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $request = $this->createRequest($firstMessageRecipient, null);

        $resultForFirst = $this->repository->findLastForMessageRecipient($firstMessageRecipient, null);
        $resultForSecond = $this->repository->findLastForMessageRecipient($secondMessageRecipient, null);

        self::assertNotNull($resultForFirst);
        self::assertSame($request->getId(), $resultForFirst->getId());
        self::assertNull($resultForSecond);
    }

    public function testLastRequestIsReturned(): void
    {
        [$addrS, $addrR] = $this->setupSenderAndRecipient();
        $messageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $this->createRequest($messageRecipient, 'news.example.com', UnsubscribeRequest::STATUS_FAILED);
        $lastRequest = $this->createRequest($messageRecipient, 'news.example.com');

        $result = $this->repository->findLastForMessageRecipient($messageRecipient, 'news.example.com');

        self::assertNotNull($result);
        self::assertSame($lastRequest->getId(), $result->getId());
        self::assertSame(UnsubscribeRequest::STATUS_SENT, $result->getStatus());
    }

    public function testTruncateOlderDeletesOnlyOldRequests(): void
    {
        [$addrS, $addrR] = $this->setupSenderAndRecipient();
        $messageRecipient = $this->setupMessageRecipient($addrS, $addrR);
        $oldRequest = $this->createRequest($messageRecipient, 'old.example.com');
        $recentRequest = $this->createRequest($messageRecipient, 'recent.example.com');
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE unsubscribe_request SET created_at = :date WHERE id = :id',
            [
                'date' => (new \DateTimeImmutable('-91 days'))->format('Y-m-d H:i:s'),
                'id' => $oldRequest->getId(),
            ],
        );
        $oldRequestId = $oldRequest->getId();
        $recentRequestId = $recentRequest->getId();
        $this->entityManager->clear();

        $deletedCount = $this->repository->truncateOlder(UnsubscribeRequest::RETENTION_DAYS);

        self::assertSame(1, $deletedCount);
        self::assertNull($this->repository->find($oldRequestId));
        self::assertNotNull($this->repository->find($recentRequestId));
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
    ): UnsubscribeRequest {
        $request = new UnsubscribeRequest(
            $messageRecipient,
            $listId,
            ListUnsubscribe::METHOD_ONE_CLICK,
            $status,
            null,
        );
        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return $request;
    }
}
