<?php

namespace App\Entity;

use App\Model\ListUnsubscribe;
use App\Repository\UnsubscribeRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A request to unsubscribe an address from a mailing list.
 *
 * The request is identified by the recipient address and the List-Id, so that
 * it can be found again for the next messages of the same list. When the
 * message has no List-Id, it is only related to the message it was made from.
 *
 * The request is not linked to the message with a foreign key: it must be
 * kept after the message is deleted (see RETENTION_DAYS).
 */
#[ORM\Index(name: 'unsubscribe_request_idx_list', columns: ['address_id', 'list_id'])]
#[ORM\Index(name: 'unsubscribe_request_idx_message', columns: ['partition_tag', 'mail_id', 'rseqnum'])]
#[ORM\Index(name: 'unsubscribe_request_idx_created_at', columns: ['created_at'])]
#[ORM\Entity(repositoryClass: UnsubscribeRequestRepository::class)]
class UnsubscribeRequest
{
    /**
     * Number of days the requests are kept.
     */
    public const RETENTION_DAYS = 90;

    /**
     * The one-click request (RFC 8058) has been sent successfully.
     */
    public const STATUS_SENT = 'sent';

    /**
     * The one-click request (RFC 8058) has failed.
     */
    public const STATUS_FAILED = 'failed';

    /**
     * The user has been redirected to the HTTPS or mailto link: AgentJ cannot
     * know whether the unsubscription has been completed.
     */
    public const STATUS_INITIATED = 'initiated';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Address $address;

    /**
     * Identifier of the list, as saved in MessageListMetadata.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $listId;

    /**
     * Message recipient the request was made from.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $partitionTag;

    #[ORM\Column(type: Types::BINARY, length: 255)]
    private string $mailId;

    #[ORM\Column(type: Types::INTEGER)]
    private int $rseqnum;

    /**
     * One of the ListUnsubscribe::METHOD_* constants.
     */
    #[ORM\Column(type: Types::STRING, length: 16)]
    private string $method;

    /**
     * One of the STATUS_* constants.
     */
    #[ORM\Column(type: Types::STRING, length: 16)]
    private string $status;

    /**
     * User who made the request (the recipient, or an administrator).
     */
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
        return $this->method === ListUnsubscribe::METHOD_ONE_CLICK;
    }

    public function getStatus(): string
    {
        return $this->status;
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
