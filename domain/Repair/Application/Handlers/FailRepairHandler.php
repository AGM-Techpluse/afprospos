<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Contracts\PaymentTransactionLookup;
use Domain\Repair\Application\Commands\FailRepairCommand;
use Domain\Repair\Domain\Events\RepairFailed;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Event;

/** `reason` has no dedicated schema column (DBDD gives none) — it's captured in the audit record's context, not persisted on repair_jobs. */
final class FailRepairHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly PaymentTransactionLookup $payments,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(FailRepairCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $actor = new StaffId($command->failedByStaffId);
            $downPayment = $this->payments->latestTransactionForPurpose('repair_job', $jobId->value, 'repair_down_payment');
            $confirmedDownPaymentMinor = ($downPayment['status'] ?? null) === 'confirmed' ? $downPayment['amount_minor'] : 0;

            if ($command->serviceChargeMinor > $confirmedDownPaymentMinor) {
                throw new \InvalidArgumentException('Service charge cannot be greater than the confirmed down payment.');
            }

            $refundDueMinor = max(0, $confirmedDownPaymentMinor - $command->serviceChargeMinor);

            $job->fail();
            $job->recordFailureSettlement(new Money($command->serviceChargeMinor), new Money($refundDueMinor));
            $this->repairJobs->save($job);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairFailed',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: null,
                afterState: [
                    'reason' => $command->reason,
                    'service_charge_minor' => $command->serviceChargeMinor,
                    'refund_due_minor' => $refundDueMinor,
                ],
            );

            Event::dispatch(new RepairFailed($jobId->value, $command->reason, CarbonImmutable::now()));
        });
    }
}
