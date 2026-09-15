<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\ExpireRepairAuthorizationCommand;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;

/**
 * Two-tick buffer, no separate grace-period config: a job first flips
 * `awaiting_authorization -> payment_overdue` once its deadline passes,
 * then `payment_overdue -> expired_cancelled` on the *next* run that
 * still finds it overdue — giving one scheduler tick of buffer without a
 * second configurable window.
 */
final class ExpireRepairAuthorizationHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ExpireRepairAuthorizationCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);

            $eventType = match ($job->status()) {
                'awaiting_authorization' => 'RepairPaymentOverdue',
                'payment_overdue' => 'RepairAuthorizationExpired',
                default => null,
            };

            if ($eventType === null) {
                return;
            }

            if ($job->status() === 'awaiting_authorization') {
                $job->markPaymentOverdue();
            } else {
                $job->markExpiredCancelled();
            }

            $this->repairJobs->save($job);

            $this->audit->record(
                module: 'Repair',
                eventType: $eventType,
                actorStaffId: null,
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: null,
                afterState: ['status' => $job->status()],
            );
        });
    }
}
