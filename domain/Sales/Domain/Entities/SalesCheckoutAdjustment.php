<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Entities;

use DateTimeImmutable;
use Domain\Sales\Domain\Services\CheckoutPricingService;
use Domain\Sales\Domain\ValueObjects\SalesCheckoutAdjustmentId;
use Domain\Shared\Domain\ValueObjects\Money;

/**
 * A single priced adjustment against a checkout (DBDD §13.3, MKT-BR-07/08).
 * `sourceId` is an opaque cross-module reference — none of promotion/
 * referral/trade-in/store-credit's owning modules exist yet, so nothing
 * validates it, matching how Commission stores source_type/source_id.
 *
 * MKT-BR-15: every adjustment starts `pending` and must be approved
 * (Shop Owner only, enforced at the permission layer) before the checkout
 * it belongs to can be paid — see `SalesCheckout::markPaid()`.
 */
final class SalesCheckoutAdjustment
{
    private function __construct(
        private readonly ?SalesCheckoutAdjustmentId $id,
        private readonly string $type,
        private readonly int $sourceId,
        private readonly Money $amount,
        private readonly int $appliedOrder,
        private string $approvalStatus,
        private ?int $approvedByStaffId,
        private ?DateTimeImmutable $approvedAt,
    ) {}

    public static function create(string $type, int $sourceId, Money $amount): self
    {
        return new self(null, $type, $sourceId, $amount, CheckoutPricingService::appliedOrderFor($type), 'pending', null, null);
    }

    public static function reconstitute(
        SalesCheckoutAdjustmentId $id,
        string $type,
        int $sourceId,
        Money $amount,
        int $appliedOrder,
        string $approvalStatus,
        ?int $approvedByStaffId,
        ?DateTimeImmutable $approvedAt,
    ): self {
        return new self($id, $type, $sourceId, $amount, $appliedOrder, $approvalStatus, $approvedByStaffId, $approvedAt);
    }

    public function approve(int $approvedByStaffId, DateTimeImmutable $now): void
    {
        $this->approvalStatus = 'approved';
        $this->approvedByStaffId = $approvedByStaffId;
        $this->approvedAt = $now;
    }

    public function isApproved(): bool
    {
        return $this->approvalStatus === 'approved';
    }

    public function id(): ?SalesCheckoutAdjustmentId
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function sourceId(): int
    {
        return $this->sourceId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function appliedOrder(): int
    {
        return $this->appliedOrder;
    }

    public function approvalStatus(): string
    {
        return $this->approvalStatus;
    }

    public function approvedByStaffId(): ?int
    {
        return $this->approvedByStaffId;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }
}
