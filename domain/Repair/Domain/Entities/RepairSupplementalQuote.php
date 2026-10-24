<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use DateTimeImmutable;
use Domain\Repair\Domain\Exceptions\InvalidRepairSupplementalQuoteTransition;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairSupplementalQuoteId;
use Domain\Shared\Domain\ValueObjects\Money;

/**
 * A "supplement" — additional cost discovered after the original quote
 * was authorized (e.g. parts turned out more expensive than estimated),
 * gated on the same trust tier that set the original quote. Mutable (a
 * rejected request may be re-requested as a new row), mirrors
 * `RepairPaymentApproval`'s shape exactly.
 */
final class RepairSupplementalQuote
{
    private function __construct(
        private readonly ?RepairSupplementalQuoteId $id,
        private readonly RepairJobId $repairJobId,
        private readonly Money $amount,
        private readonly string $reason,
        private readonly int $requestedByStaffId,
        private string $status,
        private ?int $approvedByStaffId,
        private ?DateTimeImmutable $decidedAt,
    ) {}

    public static function request(RepairJobId $repairJobId, Money $amount, string $reason, int $requestedByStaffId): self
    {
        return new self(null, $repairJobId, $amount, $reason, $requestedByStaffId, 'pending', null, null);
    }

    public static function reconstitute(
        RepairSupplementalQuoteId $id,
        RepairJobId $repairJobId,
        Money $amount,
        string $reason,
        int $requestedByStaffId,
        string $status,
        ?int $approvedByStaffId,
        ?DateTimeImmutable $decidedAt,
    ): self {
        return new self($id, $repairJobId, $amount, $reason, $requestedByStaffId, $status, $approvedByStaffId, $decidedAt);
    }

    public function approve(int $approvedByStaffId, DateTimeImmutable $now): void
    {
        $this->assertPending();
        $this->status = 'approved';
        $this->approvedByStaffId = $approvedByStaffId;
        $this->decidedAt = $now;
    }

    public function reject(int $approvedByStaffId, DateTimeImmutable $now): void
    {
        $this->assertPending();
        $this->status = 'rejected';
        $this->approvedByStaffId = $approvedByStaffId;
        $this->decidedAt = $now;
    }

    private function assertPending(): void
    {
        if ($this->status !== 'pending') {
            throw InvalidRepairSupplementalQuoteTransition::forQuote($this->id?->value ?? 0, $this->status);
        }
    }

    public function id(): ?RepairSupplementalQuoteId
    {
        return $this->id;
    }

    public function repairJobId(): RepairJobId
    {
        return $this->repairJobId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function requestedByStaffId(): int
    {
        return $this->requestedByStaffId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function approvedByStaffId(): ?int
    {
        return $this->approvedByStaffId;
    }

    public function decidedAt(): ?DateTimeImmutable
    {
        return $this->decidedAt;
    }
}
