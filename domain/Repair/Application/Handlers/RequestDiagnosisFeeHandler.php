<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\Contracts\PaymentInitiationService;
use Domain\Payments\Application\Contracts\PaymentTransactionLookup;
use Domain\Repair\Application\Commands\RequestDiagnosisFeeCommand;
use Domain\Repair\Domain\Exceptions\InvalidDiagnosisFeeState;
use Domain\Repair\Domain\Exceptions\InvalidRepairTransition;
use Domain\Repair\Domain\Repositories\RepairDiagnosisFeeRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;

/**
 * Collects the diagnosis fee as its own payment. Only a fee the manager has approved
 * can be collected, and only once: a fee already paid or awaiting confirmation is refused.
 */
final class RequestDiagnosisFeeHandler
{
    /** A cancelled job still owes its approved fee: the customer declined the repair after diagnosis, and the fee is kept. */
    private const CHARGEABLE_STATUSES = ['diagnosing', 'awaiting_authorization', 'unrepairable', 'payment_overdue', 'cancelled'];

    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly RepairDiagnosisFeeRepository $fees,
        private readonly PaymentInitiationService $payments,
        private readonly PaymentTransactionLookup $transactions,
    ) {}

    public function handle(RequestDiagnosisFeeCommand $command): int
    {
        $jobId = new RepairJobId($command->repairJobId);
        $job = $this->repairJobs->get($jobId);

        if (! in_array($job->status(), self::CHARGEABLE_STATUSES, true)) {
            throw InvalidRepairTransition::forJob($command->repairJobId, $job->status(), 'diagnosis_fee');
        }

        $fee = $this->fees->find($jobId) ?? throw InvalidDiagnosisFeeState::forJob($command->repairJobId, 'no fee has been set.');

        if ($fee->decision() !== 'approved') {
            throw InvalidDiagnosisFeeState::forJob($command->repairJobId, 'the fee is not due.');
        }

        $existing = $this->transactions->latestTransactionForPurpose('repair_job', $command->repairJobId, 'repair_diagnosis_fee');

        if ($existing !== null && $existing['status'] === 'confirmed') {
            throw InvalidDiagnosisFeeState::forJob($command->repairJobId, 'it has already been paid.');
        }

        if ($existing !== null && in_array($existing['status'], ['pending', 'payment_pending_confirmation'], true)) {
            throw InvalidDiagnosisFeeState::forJob($command->repairJobId, 'a payment for it is already awaiting confirmation.');
        }

        return $this->payments->initiate(new InitiatePaymentCommand(
            payableType: 'repair_job',
            payableId: $command->repairJobId,
            purpose: 'repair_diagnosis_fee',
            method: $command->method,
            amountMinor: $fee->amountMinor(),
            initiatedByStaffId: $command->requestedByStaffId,
        ))->transactionId;
    }
}
