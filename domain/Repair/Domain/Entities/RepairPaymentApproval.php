<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use DateTimeImmutable;
use Domain\Repair\Domain\Exceptions\InvalidRepairPaymentApprovalTransition;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairPaymentApprovalId;
use Domain\Shared\Domain\ValueObjects\Money;

/**
 * REP-BR-27/28: a frontdesk request to record a completed repair's
 * payment, gated on Shop Owner approval — the approval itself is what
 * authorizes `RepairJob::recordFinancialStatus()`. Mutable (a rejected
 * request may be re-requested as a new row), mirrors
 * `Domain\Warranty\Domain\Entities\TradeInAssessment`'s shape.
 */
final class RepairPaymentApproval
{
    private function __construct(
        private readonly ?RepairPaymentApprovalId $id,
        private readonly RepairJobId $repairJobId,
        private readonly string $requestedFinancialStatus,
        private readonly Money $amount,
        private readonly string $method,
        private readonly int $requestedByStaffId,
        private string $status,
        private ?int $approvedByStaffId,
        private ?DateTimeImmutable $decidedAt,
    ) {}

    /**
     * @param  'partially_paid'|'fully_paid'  $requestedFinancialStatus
     * @param  'cash'|'pos_terminal'|'bank_transfer'  $method
     */
    public static function request(RepairJobId $repairJobId, string $requestedFinancialStatus, Money $amount, string $method, int $requestedByStaffId): self
    {
        return new self(null, $repairJobId, $requestedFinancialStatus, $amount, $method, $requestedByStaffId, 'pending', null, null);
    }

    public static function reconstitute(
        RepairPaymentApprovalId $id,
        RepairJobId $repairJobId,
        string $requestedFinancialStatus,
        Money $amount,
        string $method,
        int $requestedByStaffId,
        string $status,
        ?int $approvedByStaffId,
        ?DateTimeImmutable $decidedAt,
    ): self {
        return new self($id, $repairJobId, $requestedFinancialStatus, $amount, $method, $requestedByStaffId, $status, $approvedByStaffId, $decidedAt);
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

    public function id(): ?RepairPaymentApprovalId
    {
        return $this->id;
    }

    public function repairJobId(): RepairJobId
    {
        return $this->repairJobId;
    }

    public function requestedFinancialStatus(): string
    {
        return $this->requestedFinancialStatus;
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
