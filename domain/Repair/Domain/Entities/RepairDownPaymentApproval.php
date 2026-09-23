<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use DateTimeImmutable;
use Domain\Repair\Domain\Exceptions\InvalidRepairPaymentApprovalTransition;
use Domain\Repair\Domain\ValueObjects\RepairDownPaymentApprovalId;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\Money;

/**
 * Mirrors `RepairPaymentApproval`'s shape exactly, but for the pre-work
 * down-payment stage instead of the post-completion settlement — kept as
 * its own entity rather than a branch inside `RepairPaymentApproval`
 * since the two happen at structurally different points in the repair
 * lifecycle (before vs. after work is done) and this codebase prefers a
 * distinct, single-purpose entity per business concept (mirrors how
 * `TradeInAssessment` and `ReturnRequest` stay separate rather than one
 * generic "assessment" class). Frontdesk requests once the customer has
 * actually paid the amount the Manager set via `RepairJob::setAuthorizationRequirements()`;
 * approval is what authorizes `RepairJob::grantAuthorization()` and
 * initiates the real payment transaction — mutable, a rejected request
 * may be re-requested as a new row.
 */
final class RepairDownPaymentApproval
{
    private function __construct(
        private readonly ?RepairDownPaymentApprovalId $id,
        private readonly RepairJobId $repairJobId,
        private readonly Money $amount,
        private readonly string $method,
        private readonly int $requestedByStaffId,
        private string $status,
        private ?int $approvedByStaffId,
        private ?DateTimeImmutable $decidedAt,
    ) {}

    /** @param  'cash'|'pos_terminal'|'bank_transfer'  $method */
    public static function request(RepairJobId $repairJobId, Money $amount, string $method, int $requestedByStaffId): self
    {
        return new self(null, $repairJobId, $amount, $method, $requestedByStaffId, 'pending', null, null);
    }

    public static function reconstitute(
        RepairDownPaymentApprovalId $id,
        RepairJobId $repairJobId,
        Money $amount,
        string $method,
        int $requestedByStaffId,
        string $status,
        ?int $approvedByStaffId,
        ?DateTimeImmutable $decidedAt,
    ): self {
        return new self($id, $repairJobId, $amount, $method, $requestedByStaffId, $status, $approvedByStaffId, $decidedAt);
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
            throw InvalidRepairPaymentApprovalTransition::forApproval($this->id?->value ?? 0, $this->status);
        }
    }

    public function id(): ?RepairDownPaymentApprovalId
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

    public function method(): string
    {
        return $this->method;
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
