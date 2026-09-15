<?php

declare(strict_types=1);

namespace Domain\Collection\Domain\Entities;

use DateTimeImmutable;
use Domain\Collection\Domain\Exceptions\InvalidCollectionTransition;
use Domain\Collection\Domain\Policies\CollectionTransitionPolicy;
use Domain\Collection\Domain\ValueObjects\CollectionCaseId;

/** Mutable aggregate (DBDD §12.1), mirrors SalesCheckout's shape. */
final class CollectionCase
{
    private function __construct(
        private readonly ?CollectionCaseId $id,
        private readonly string $sourceType,
        private readonly int $sourceId,
        private readonly int $shopId,
        private readonly int $originatingShopId,
        private readonly string $context,
        private string $status,
        private DateTimeImmutable $collectionDeadlineAt,
        private ?DateTimeImmutable $abandonmentThresholdAt,
        private ?array $storageFeePolicySnapshot,
        private int $accruedStorageFeeMinor,
        private ?string $shopOverrideReason,
    ) {}

    public static function open(
        string $sourceType,
        int $sourceId,
        int $shopId,
        int $originatingShopId,
        string $context,
        DateTimeImmutable $collectionDeadlineAt,
        ?DateTimeImmutable $abandonmentThresholdAt,
        ?array $storageFeePolicySnapshot,
    ): self {
        return new self(null, $sourceType, $sourceId, $shopId, $originatingShopId, $context, 'pending', $collectionDeadlineAt, $abandonmentThresholdAt, $storageFeePolicySnapshot, 0, null);
    }

    public static function reconstitute(
        CollectionCaseId $id,
        string $sourceType,
        int $sourceId,
        int $shopId,
        int $originatingShopId,
        string $context,
        string $status,
        DateTimeImmutable $collectionDeadlineAt,
        ?DateTimeImmutable $abandonmentThresholdAt,
        ?array $storageFeePolicySnapshot,
        int $accruedStorageFeeMinor,
        ?string $shopOverrideReason,
    ): self {
        return new self($id, $sourceType, $sourceId, $shopId, $originatingShopId, $context, $status, $collectionDeadlineAt, $abandonmentThresholdAt, $storageFeePolicySnapshot, $accruedStorageFeeMinor, $shopOverrideReason);
    }

    public function markOverdue(): void
    {
        $this->assertTransition('overdue');
        $this->status = 'overdue';
    }

    public function markAbandoned(): void
    {
        $this->assertTransition('abandoned');
        $this->status = 'abandoned';
    }

    public function resolve(): void
    {
        $this->assertTransition('resolved');
        $this->status = 'resolved';
    }

    public function accrueFee(int $minor): void
    {
        $this->assertMutable();
        $this->accruedStorageFeeMinor += $minor;
    }

    public function extendDeadline(DateTimeImmutable $newDeadline, ?DateTimeImmutable $newAbandonmentThreshold): void
    {
        $this->assertMutable();
        $this->collectionDeadlineAt = $newDeadline;
        $this->abandonmentThresholdAt = $newAbandonmentThreshold;
    }

    public function recordOverrideReason(string $reason): void
    {
        $this->assertMutable();
        $this->shopOverrideReason = $reason;
    }

    private function assertMutable(): void
    {
        if ($this->status === 'resolved') {
            throw InvalidCollectionTransition::forCase($this->id?->value ?? 0, $this->status, $this->status);
        }
    }

    private function assertTransition(string $to): void
    {
        if (! (new CollectionTransitionPolicy)->canTransition($this->status, $to)) {
            throw InvalidCollectionTransition::forCase($this->id?->value ?? 0, $this->status, $to);
        }
    }

    public function id(): ?CollectionCaseId
    {
        return $this->id;
    }

    public function sourceType(): string
    {
        return $this->sourceType;
    }

    public function sourceId(): int
    {
        return $this->sourceId;
    }

    public function shopId(): int
    {
        return $this->shopId;
    }

    public function originatingShopId(): int
    {
        return $this->originatingShopId;
    }

    public function context(): string
    {
        return $this->context;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function collectionDeadlineAt(): DateTimeImmutable
    {
        return $this->collectionDeadlineAt;
    }

    public function abandonmentThresholdAt(): ?DateTimeImmutable
    {
        return $this->abandonmentThresholdAt;
    }

    public function storageFeePolicySnapshot(): ?array
    {
        return $this->storageFeePolicySnapshot;
    }

    public function accruedStorageFeeMinor(): int
    {
        return $this->accruedStorageFeeMinor;
    }

    public function shopOverrideReason(): ?string
    {
        return $this->shopOverrideReason;
    }
}
