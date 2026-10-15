<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\SetDiagnosisFeeCommand;
use Domain\Repair\Domain\Entities\RepairDiagnosisFee;
use Domain\Repair\Domain\Exceptions\InvalidRepairTransition;
use Domain\Repair\Domain\Repositories\RepairDiagnosisFeeRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * Frontdesk sets the diagnosis fee once diagnosis has been carried out. The manager
 * still has to approve it before it can be collected.
 */
final class SetDiagnosisFeeHandler
{
    private const SETTABLE_STATUSES = ['diagnosing', 'awaiting_authorization'];

    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly RepairDiagnosisFeeRepository $fees,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(SetDiagnosisFeeCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->get($jobId);

            if (! in_array($job->status(), self::SETTABLE_STATUSES, true)) {
                throw InvalidRepairTransition::forJob($command->repairJobId, $job->status(), 'diagnosis_fee');
            }

            $fee = $this->fees->find($jobId);

            if ($fee === null) {
                $fee = RepairDiagnosisFee::set($jobId, $command->amountMinor, $command->setByStaffId);
            } else {
                $fee->changeAmount($command->amountMinor, $command->setByStaffId);
            }

            $this->fees->save($fee);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairDiagnosisFeeSet',
                actorStaffId: new StaffId($command->setByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: null,
                afterState: ['amount_minor' => $command->amountMinor, 'decision' => $fee->decision()],
            );
        });
    }
}
