<?php

declare(strict_types=1);

namespace Domain\Commission\Domain\Entities;

use Carbon\CarbonImmutable;
use Domain\Commission\Domain\ValueObjects\CommissionLedgerEntryId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use InvalidArgumentException;

/**
 * One append-only row of the commission ledger (DBDD §17.1). Never mutated
 * after creation — a correction is always a new row (`entry_type` =
 * `reversal`/`adjustment`), never an edit of this one. This phase only
 * produces `earned`/`earned` rows for flat, pre-configured amounts; the
 * other enum values exist for later phase-8 work (rate-based commission,
 * reversals, payouts).
 */
final class CommissionLedgerEntry
{
    private const SOURCE_TYPES = ['sale', 'repair'];

    private function __construct(
        private readonly ?CommissionLedgerEntryId $id,
        private readonly StaffId $staffId,
        private readonly string $sourceType,
        private readonly int $sourceId,
        private readonly Money $commissionableAmount,
        private readonly float $rateSnapshotPercent,
        private readonly string $roleSnapshot,
        private readonly string $entryType,
        private readonly ?int $reversalOfEntryId,
        private readonly string $status,
        private readonly ShopId $shopId,
        private readonly ?CarbonImmutable $createdAt,
    ) {
        if (! in_array($sourceType, self::SOURCE_TYPES, true)) {
            throw new InvalidArgumentException("Invalid commission source type [{$sourceType}].");
        }
    }

    /**
     * A flat amount earned in full at the moment it's recorded — commission
     * is expressed as "100% of the configured flat amount" so it fits the
     * same rate-snapshot schema a future percentage-rate policy will use.
     */
    public static function earn(StaffId $staffId, string $sourceType, int $sourceId, Money $amount, string $roleSnapshot, ShopId $shopId): self
    {
        return new self(
            id: null,
            staffId: $staffId,
            sourceType: $sourceType,
            sourceId: $sourceId,
            commissionableAmount: $amount,
            rateSnapshotPercent: 100.00,
            roleSnapshot: $roleSnapshot,
            entryType: 'earned',
            reversalOfEntryId: null,
            status: 'earned',
            shopId: $shopId,
            createdAt: null,
        );
    }

    public static function reconstitute(
        CommissionLedgerEntryId $id,
        StaffId $staffId,
        string $sourceType,
        int $sourceId,
        Money $commissionableAmount,
        float $rateSnapshotPercent,
        string $roleSnapshot,
        string $entryType,
        ?int $reversalOfEntryId,
        string $status,
        ShopId $shopId,
        CarbonImmutable $createdAt,
    ): self {
        return new self(
            $id, $staffId, $sourceType, $sourceId, $commissionableAmount,
            $rateSnapshotPercent, $roleSnapshot, $entryType, $reversalOfEntryId,
            $status, $shopId, $createdAt,
        );
    }

    public function id(): ?CommissionLedgerEntryId
    {
        return $this->id;
    }

    public function staffId(): StaffId
    {
        return $this->staffId;
    }

    public function sourceType(): string
    {
        return $this->sourceType;
    }

    public function sourceId(): int
    {
        return $this->sourceId;
    }

    public function commissionableAmount(): Money
    {
        return $this->commissionableAmount;
    }

    public function rateSnapshotPercent(): float
    {
        return $this->rateSnapshotPercent;
    }

    public function roleSnapshot(): string
    {
        return $this->roleSnapshot;
    }

    public function entryType(): string
    {
        return $this->entryType;
    }

    public function reversalOfEntryId(): ?int
    {
        return $this->reversalOfEntryId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function shopId(): ShopId
    {
        return $this->shopId;
    }

    public function createdAt(): ?CarbonImmutable
    {
        return $this->createdAt;
    }
}
