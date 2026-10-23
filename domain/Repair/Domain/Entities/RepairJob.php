<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use DateTimeImmutable;
use Domain\Repair\Domain\Exceptions\InvalidRepairTransition;
use Domain\Repair\Domain\Exceptions\RepairJobIsClosed;
use Domain\Repair\Domain\Exceptions\RepairTransferNotAllowed;
use Domain\Repair\Domain\Policies\RepairTransitionPolicy;
use Domain\Repair\Domain\ValueObjects\DeviceModelId;
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
        private ShopId $shopId,
        private readonly ?CustomerId $customerId,
        private readonly ?string $walkInName,
        private readonly ?string $walkInPhone,
        private readonly ?string $walkInEmail,
        private readonly string $deviceMake,
        private readonly string $deviceModel,
        private readonly ?DeviceModelId $deviceModelId,
        private readonly ?string $reportedIssue,
        private readonly ?string $deviceImeiSerial,
        private string $deviceLockType,
        private ?string $deviceLockValue,
        private ?StaffId $technicianStaffId,
        private readonly ?Money $labourCharge,
        private ?Money $repairCost,
        private ?Money $downPaymentRequired,
        private ?DateTimeImmutable $downPaymentDeadlineAt,
        private string $status,
        private string $financialStatus,
        private ?DateTimeImmutable $estimatedCollectionDate,
        private ?string $unrepairableSettlementState,
        private ?string $resolutionNotes,
        private ?Money $failureServiceCharge,
        private ?Money $failureRefundDue,
    ) {}

    /** @param  'none'|'code'|'pattern'  $deviceLockType */
    public static function intake(ShopId $shopId, ?CustomerId $customerId, string $deviceMake, string $deviceModel, ?Money $labourCharge = null, ?string $reportedIssue = null, ?string $deviceImeiSerial = null, string $deviceLockType = 'none', ?string $deviceLockValue = null, ?string $walkInName = null, ?string $walkInPhone = null, ?string $walkInEmail = null, ?DeviceModelId $deviceModelId = null): self
    {
        return new self(null, $shopId, $customerId, $walkInName, $walkInPhone, $walkInEmail, $deviceMake, $deviceModel, $deviceModelId, $reportedIssue, $deviceImeiSerial, $deviceLockType, $deviceLockValue, null, $labourCharge, null, null, null, 'received', 'unpaid', null, null, null, null, null);
    }

    public static function reconstitute(
        RepairJobId $id,
        ShopId $shopId,
        ?CustomerId $customerId,
        string $deviceMake,
        string $deviceModel,
        ?string $reportedIssue,
        ?string $deviceImeiSerial,
        string $deviceLockType,
        ?string $deviceLockValue,
        ?StaffId $technicianStaffId,
        ?Money $labourCharge,
        ?Money $repairCost,
        ?Money $downPaymentRequired,
        ?DateTimeImmutable $downPaymentDeadlineAt,
        string $status,
        string $financialStatus,
        ?DateTimeImmutable $estimatedCollectionDate,
        ?string $unrepairableSettlementState,
        ?string $resolutionNotes,
        ?Money $failureServiceCharge = null,
        ?Money $failureRefundDue = null,
        ?string $walkInName = null,
        ?string $walkInPhone = null,
        ?string $walkInEmail = null,
        ?DeviceModelId $deviceModelId = null,
    ): self {
        return new self($id, $shopId, $customerId, $walkInName, $walkInPhone, $walkInEmail, $deviceMake, $deviceModel, $deviceModelId, $reportedIssue, $deviceImeiSerial, $deviceLockType, $deviceLockValue, $technicianStaffId, $labourCharge, $repairCost, $downPaymentRequired, $downPaymentDeadlineAt, $status, $financialStatus, $estimatedCollectionDate, $unrepairableSettlementState, $resolutionNotes, $failureServiceCharge, $failureRefundDue);
    }

    /** Staff have saved the diagnosis and send it for the manager to quote and authorize. */
    public function sendToQuote(): void
    {
        $this->assertTransition('awaiting_authorization');
        $this->status = 'awaiting_authorization';
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

    /**
     * The customer-agreed price, only knowable once a `repairable` diagnosis
     * conclusion has been recorded — distinct from `labourCharge`, which is
     * captured at intake for a possible future technician-commission use
     * case and is never the repair's price. Editable across every open
     * post-diagnosis state (a technician may need to revise it once more
     * parts/work turn out to be needed), locked once the job closes.
     */
    /** The quote can only change before the manager authorizes it. Once approved, the amount is fixed. */
    private const REPAIR_COST_EDITABLE_STATUSES = ['awaiting_authorization', 'payment_overdue'];

    public function setRepairCost(Money $cost): void
    {
        if (! in_array($this->status, self::REPAIR_COST_EDITABLE_STATUSES, true)) {
            throw InvalidRepairTransition::forJob($this->id?->value ?? 0, $this->status, 'repair_cost');
        }

        $this->repairCost = $cost;
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
        $this->assertTransition('in_progress');
        $this->status = 'in_progress';
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

    public function transferToShop(ShopId $targetShopId): void
    {
        if ($this->shopId->value === $targetShopId->value) {
            throw RepairTransferNotAllowed::sameShop($targetShopId->value);
        }

        if (in_array($this->status, ['completed', 'unrepairable', 'expired_cancelled', 'cancelled'], true)) {
            throw RepairTransferNotAllowed::closed($this->id?->value ?? 0, $this->status);
        }

        $this->shopId = $targetShopId;
        $this->technicianStaffId = null;
    }

    /**
     * `resolutionNotes` is optional even here — a repair that used parts
     * already has the part reservations as its record of what was done;
     * this is the only record for a parts-less repair (reflow, a software
     * fix, reseating a cable), but nothing forces a technician to fill it.
     *
     * REP-BR-27: completing a repair no longer records a financial status
     * directly — `financial_status` stays whatever it already was
     * (`unpaid` at intake) until an approved `RepairPaymentApproval`
     * authorizes `recordFinancialStatus()`.
     */
    public function complete(?string $resolutionNotes = null): void
    {
        $this->assertTransition('qc_pending');
        $this->status = 'qc_pending';
        $this->resolutionNotes = $resolutionNotes;
    }

    /** QC passed, or not testable with a recorded note — the repair is done and the device can be handed over. */
    public function passQualityCheck(): void
    {
        $this->assertTransition('handover');
        $this->status = 'handover';
    }

    /** The device has been released from collection: the repair is finished. */
    public function markCollected(): void
    {
        $this->assertTransition('completed');
        $this->status = 'completed';
    }

    /** QC failed and the admin's policy sends the job back to diagnosis. */
    public function returnToDiagnosisAfterFailedQc(): void
    {
        $this->assertTransition('diagnosing');
        $this->status = 'diagnosing';
    }

    /** Cancels an open job (customer declined the quote, QC failed under a cancel policy, or staff stopped it). The reason is kept in resolution notes. */
    public function cancel(string $reason): void
    {
        $this->assertTransition('cancelled');
        $this->status = 'cancelled';
        $this->resolutionNotes = $reason;
    }

    public function fail(): void
    {
        $this->assertTransition('failed_requires_resolution');
        $this->status = 'failed_requires_resolution';
    }

    public function recordFailureSettlement(Money $serviceCharge, Money $refundDue): void
    {
        $this->failureServiceCharge = $serviceCharge;
        $this->failureRefundDue = $refundDue;
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

    /**
     * REP-BR-27/28: the only legitimate way `financial_status` changes
     * after intake — callable only once the repair is `completed`, and
     * only by the approved-payment-approval Handler (never a bare status
     * pick on the completion form).
     *
     * @param  'unpaid'|'partially_paid'|'fully_paid'  $financialStatus
     */
    public function recordFinancialStatus(string $financialStatus): void
    {
        if (! in_array($this->status, ['handover', 'completed'], true)) {
            throw InvalidRepairTransition::forJob($this->id?->value ?? 0, $this->status, 'handover');
        }

        $this->financialStatus = $financialStatus;
    }

    /** Implementation Plan Phase 6 exit criterion: parts may never be reserved before authorization. */
    public function assertPartsReservable(): void
    {
        // Parts are set aside as soon as the fault is diagnosed, so the manager can price them into the quote.
        // They are only fitted (consumed) once the repair is in progress.
        if (! in_array($this->status, ['diagnosing', 'awaiting_authorization', 'awaiting_parts', 'in_progress'], true)) {
            throw InvalidRepairTransition::forJob($this->id?->value ?? 0, $this->status, 'awaiting_parts');
        }
    }

    /** REP-BR-28: a payment-recording request may only be raised once the repair is completed. */
    public function assertCompleted(): void
    {
        if (! in_array($this->status, ['handover', 'completed'], true)) {
            throw InvalidRepairTransition::forJob($this->id?->value ?? 0, $this->status, 'handover');
        }
    }

    /** A down-payment approval request may only be raised once the Manager has set requirements (or the job has gone overdue waiting on them). */
    public function assertAwaitingDownPayment(): void
    {
        if (! in_array($this->status, ['awaiting_authorization', 'payment_overdue'], true)) {
            throw InvalidRepairTransition::forJob($this->id?->value ?? 0, $this->status, 'awaiting_authorization');
        }
    }

    public function setEstimatedCollectionDate(?DateTimeImmutable $date): void
    {
        $this->estimatedCollectionDate = $date;
    }

    /**
     * @param  'none'|'code'|'pattern'  $type
     *
     * Blocked once the job has reached handover or any closed state — the
     * technician's work on the device is over by then, so a passcode
     * changing at that point reads as tampering rather than legitimate
     * repair work, not just a theoretical risk.
     */
    public function setDeviceLock(string $type, ?string $value): void
    {
        if (in_array($this->status, ['handover', 'completed', 'cancelled', 'unrepairable', 'expired_cancelled'], true)) {
            throw InvalidRepairTransition::forJob($this->id?->value ?? 0, $this->status, 'device_lock_update');
        }

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

    /** Null for a walk-in repair with no customer on record. */
    public function customerId(): ?CustomerId
    {
        return $this->customerId;
    }

    /** Only meaningful when customerId() is null — a walk-in's contact details captured at intake for follow-up. */
    public function walkInName(): ?string
    {
        return $this->walkInName;
    }

    public function walkInPhone(): ?string
    {
        return $this->walkInPhone;
    }

    public function walkInEmail(): ?string
    {
        return $this->walkInEmail;
    }

    public function deviceMake(): string
    {
        return $this->deviceMake;
    }

    public function deviceModel(): string
    {
        return $this->deviceModel;
    }

    public function deviceModelId(): ?DeviceModelId
    {
        return $this->deviceModelId;
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

    public function labourCharge(): ?Money
    {
        return $this->labourCharge;
    }

    public function repairCost(): ?Money
    {
        return $this->repairCost;
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

    public function failureServiceCharge(): ?Money
    {
        return $this->failureServiceCharge;
    }

    public function failureRefundDue(): ?Money
    {
        return $this->failureRefundDue;
    }
}
