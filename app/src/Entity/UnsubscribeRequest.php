<?php

namespace App\Entity;

use App\Model\UnsubscribeMethods;
use App\Repository\UnsubscribeRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A request to unsubscribe an address from a mailing list.
 *
 * The message from which the request was made is referenced without foreign
 * key: the history of the requests must survive the purge of the messages,
 * and the requests of a same list are related by the address and the List-Id.
 */
#[ORM\Index(name: 'unsubscribe_request_idx_message', columns: ['partition_tag', 'mail_id', 'rseqnum'])]
#[ORM\Index(name: 'unsubscribe_request_idx_created_at', columns: ['created_at'])]
#[ORM\Entity(repositoryClass: UnsubscribeRequestRepository::class)]
class UnsubscribeRequest
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_INITIATED = 'initiated';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Address $address;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $listId;

    #[ORM\Column(type: Types::INTEGER)]
    private int $partitionTag;

    #[ORM\Column(type: Types::BINARY, length: 255)]
    private string $mailId;

    #[ORM\Column(type: Types::INTEGER)]
    private int $rseqnum;

    #[ORM\Column(type: Types::STRING, length: 16)]
    private string $method;

    #[ORM\Column(type: Types::STRING, length: 16)]
    private string $status;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $requestedBy;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        MessageRecipient $messageRecipient,
        ?string $listId,
        string $method,
        string $status,
        ?User $requestedBy,
    ) {
        $address = $messageRecipient->getAddress();
        if ($address === null) {
            throw new \InvalidArgumentException('The message recipient has no address.');
        }

        $this->address = $address;
        $this->listId = $listId;
        $this->partitionTag = $messageRecipient->getPartitionTag();
        $this->mailId = $messageRecipient->getMailId();
        $this->rseqnum = (int) $messageRecipient->getRseqnum();
        $this->method = $method;
        $this->status = $status;
        $this->requestedBy = $requestedBy;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getListId(): ?string
    {
        return $this->listId;
    }

    public function getPartitionTag(): int
    {
        return $this->partitionTag;
    }

    public function getMailId(): string
    {
        return $this->mailId;
    }

    public function getRseqnum(): int
    {
        return $this->rseqnum;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function isOneClick(): bool
    {
        return $this->method === UnsubscribeMethods::METHOD_ONE_CLICK;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * The one-click request has been accepted by the list server.
     */
    public function markAsSent(): void
    {
        $this->status = self::STATUS_SENT;
    }

    /**
     * The one-click request has been refused by the list server, or could not
     * be sent.
     */
    public function markAsFailed(): void
    {
        $this->status = self::STATUS_FAILED;
    }

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
