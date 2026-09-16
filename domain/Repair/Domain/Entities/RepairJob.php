<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use DateTimeImmutable;
use Domain\Repair\Domain\Exceptions\InvalidRepairTransition;
use Domain\Repair\Domain\Exceptions\RepairJobIsClosed;
use Domain\Repair\Domain\Policies\RepairTransitionPolicy;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use InvalidArgumentException;

/**
 * A mutable aggregate covering intake through completion/unrepairable
 * (DBDD §11.1, §17.1) — mirrors SalesCheckout's shape (private
 * constructor, guarded mutators, ordinary reads never re-derive legality
 * beyond `RepairTransitionPolicy`). `financial_status` is modeled
 * independently of `repair_status`/device disposition per the
 * Implementation Plan's Phase 6 goal.
 */
final class RepairJob
{
    private function __construct(
        private readonly ?RepairJobId $id,
        private readonly ShopId $shopId,
        private readonly CustomerId $customerId,
        private readonly string $deviceMake,
        private readonly string $deviceModel,
        private readonly ?string $reportedIssue,
        private readonly ?string $deviceImeiSerial,
        private string $deviceLockType,
        private ?string $deviceLockValue,
        private ?StaffId $technicianStaffId,
        private readonly Money $labourCharge,
        private ?Money $downPaymentRequired,
        private ?DateTimeImmutable $downPaymentDeadlineAt,
        private string $status,
        private string $financialStatus,
        private ?DateTimeImmutable $estimatedCollectionDate,
        private ?string $unrepairableSettlementState,
        private ?string $resolutionNotes,
    ) {}

    /** @param  'none'|'code'|'pattern'  $deviceLockType */
    public static function intake(ShopId $shopId, CustomerId $customerId, string $deviceMake, string $deviceModel, Money $labourCharge, ?string $reportedIssue = null, ?string $deviceImeiSerial = null, string $deviceLockType = 'none', ?string $deviceLockValue = null): self
    {
        return new self(null, $shopId, $customerId, $deviceMake, $deviceModel, $reportedIssue, $deviceImeiSerial, $deviceLockType, $deviceLockValue, null, $labourCharge, null, null, 'received', 'unpaid', null, null, null);
    }

    public static function reconstitute(
        RepairJobId $id,
        ShopId $shopId,
        CustomerId $customerId,
        string $deviceMake,
        string $deviceModel,
        ?string $reportedIssue,
        ?string $deviceImeiSerial,
        string $deviceLockType,
        ?string $deviceLockValue,
        ?StaffId $technicianStaffId,
        Money $labourCharge,
        ?Money $downPaymentRequired,
        ?DateTimeImmutable $downPaymentDeadlineAt,
        string $status,
        string $financialStatus,
        ?DateTimeImmutable $estimatedCollectionDate,
        ?string $unrepairableSettlementState,
        ?string $resolutionNotes,
    ): self {
        return new self($id, $shopId, $customerId, $deviceMake, $deviceModel, $reportedIssue, $deviceImeiSerial, $deviceLockType, $deviceLockValue, $technicianStaffId, $labourCharge, $downPaymentRequired, $downPaymentDeadlineAt, $status, $financialStatus, $estimatedCollectionDate, $unrepairableSettlementState, $resolutionNotes);
    }

    public function startDiagnosis(): void
    {
        $this->assertTransition('diagnosing');
        $this->status = 'diagnosing';
    }

    /** @param  'repairable'|'unrepairable'|'requires_further_assessment'  $outcome */
    public function recordDiagnosisOutcome(string $outcome): void
    {
        $to = match ($outcome) {
            'repairable' => 'awaiting_authorization',
            'unrepairable' => 'unrepairable',
            'requires_further_assessment' => 'diagnosing',
            default => throw new InvalidArgumentException("Invalid diagnosis outcome [{$outcome}]."),
        };

        $this->assertTransition($to);
        $this->status = $to;

        if ($to === 'unrepairable') {
            $this->unrepairableSettlementState = 'pending_decision';
        }
    }

    public function setAuthorizationRequirements(?Money $downPaymentRequired, ?DateTimeImmutable $deadline): void
    {
        if ($this->status !== 'awaiting_authorization') {
            throw InvalidRepairTransition::forJob($this->id?->value ?? 0, $this->status, 'awaiting_authorization');
        }

        $this->downPaymentRequired = $downPaymentRequired;
        $this->downPaymentDeadlineAt = $deadline;
    }

    /** Down payment is zero, or Payments has confirmed it — either way, authorization is granted. */
    public function grantAuthorization(): void
    {
        $this->assertTransition('awaiting_parts');
        $this->status = 'awaiting_parts';
    }

    public function markPaymentOverdue(): void
    {
        $this->assertTransition('payment_overdue');
        $this->status = 'payment_overdue';
    }

    public function markExpiredCancelled(): void
    {
        $this->assertTransition('expired_cancelled');
        $this->status = 'expired_cancelled';
    }

    public function start(StaffId $technicianStaffId): void
    {
        $this->assertTransition('in_progress');
        $this->status = 'in_progress';
        $this->technicianStaffId = $technicianStaffId;
    }

    /**
     * Explicit dispatch — a manager assigning/reassigning a specific
     * technician ahead of `start()`, independent of who eventually
     * clicks Start repair. Allowed at any point before the job closes;
     * `start()` still overwrites it with whoever actually starts the
     * work, since that person is definitionally the one doing it.
     */
    public function assignTechnician(StaffId $technicianStaffId): void
    {
        if (in_array($this->status, ['completed', 'unrepairable', 'expired_cancelled'], true)) {
            throw RepairJobIsClosed::forJob($this->id?->value ?? 0, $this->status);
        }

        $this->technicianStaffId = $technicianStaffId;
    }

    /**
     * `resolutionNotes` is optional even here — a repair that used parts
     * already has the part reservations as its record of what was done;
     * this is the only record for a parts-less repair (reflow, a software
     * fix, reseating a cable), but nothing forces a technician to fill it.
     *
     * @param  'unpaid'|'partially_paid'|'fully_paid'  $financialStatus
     */
    public function complete(string $financialStatus, ?string $resolutionNotes = null): void
    {
        $this->assertTransition('completed');
        $this->status = 'completed';
        $this->financialStatus = $financialStatus;
        $this->resolutionNotes = $resolutionNotes;
    }

    public function fail(): void
    {
        $this->assertTransition('failed_requires_resolution');
        $this->status = 'failed_requires_resolution';
    }

    public function resume(): void
    {
        $this->assertTransition('in_progress');
        $this->status = 'in_progress';
    }

    /** @param  'pending_decision'|'refunded'|'retained'  $settlementState */
    public function markUnrepairable(string $settlementState): void
    {
        $this->assertTransition('unrepairable');
        $this->status = 'unrepairable';
        $this->unrepairableSettlementState = $settlementState;
    }

    public function recordFinancialStatus(string $financialStatus): void
    {
        $this->financialStatus = $financialStatus;
    }

    /** Implementation Plan Phase 6 exit criterion: parts may never be reserved before authorization. */
    public function assertPartsReservable(): void
    {
        if (! in_array($this->status, ['awaiting_parts', 'in_progress'], true)) {
            throw InvalidRepairTransition::forJob($this->id?->value ?? 0, $this->status, 'awaiting_parts');
        }
    }

    public function setEstimatedCollectionDate(?DateTimeImmutable $date): void
    {
        $this->estimatedCollectionDate = $date;
    }

    /** @param  'none'|'code'|'pattern'  $type */
    public function setDeviceLock(string $type, ?string $value): void
    {
        $this->deviceLockType = $type;
        $this->deviceLockValue = $type === 'none' ? null : $value;
    }

    /** Called once the device is back with the customer (Collection's release flow) — there's no legitimate reason to keep holding their passcode/pattern after that. */
    public function clearDeviceLock(): void
    {
        $this->deviceLockType = 'none';
        $this->deviceLockValue = null;
    }

    private function assertTransition(string $to): void
    {
        if (! (new RepairTransitionPolicy)->canTransition($this->status, $to)) {
            throw InvalidRepairTransition::forJob($this->id?->value ?? 0, $this->status, $to);
        }
    }

    public function id(): ?RepairJobId
    {
        return $this->id;
    }

    public function shopId(): ShopId
    {
        return $this->shopId;
    }

    public function customerId(): CustomerId
    {
        return $this->customerId;
    }

    public function deviceMake(): string
    {
        return $this->deviceMake;
    }

    public function deviceModel(): string
    {
        return $this->deviceModel;
    }

    public function reportedIssue(): ?string
    {
        return $this->reportedIssue;
    }

    public function deviceImeiSerial(): ?string
    {
        return $this->deviceImeiSerial;
    }

    /** @return 'none'|'code'|'pattern' */
    public function deviceLockType(): string
    {
        return $this->deviceLockType;
    }

    public function deviceLockValue(): ?string
    {
        return $this->deviceLockValue;
    }

    public function technicianStaffId(): ?StaffId
    {
        return $this->technicianStaffId;
    }

    public function labourCharge(): Money
    {
        return $this->labourCharge;
    }

    public function downPaymentRequired(): ?Money
    {
        return $this->downPaymentRequired;
    }

    public function downPaymentDeadlineAt(): ?DateTimeImmutable
    {
        return $this->downPaymentDeadlineAt;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function financialStatus(): string
    {
        return $this->financialStatus;
    }

    public function estimatedCollectionDate(): ?DateTimeImmutable
    {
        return $this->estimatedCollectionDate;
    }

    public function unrepairableSettlementState(): ?string
    {
        return $this->unrepairableSettlementState;
    }

    public function resolutionNotes(): ?string
    {
        return $this->resolutionNotes;
    }
}
