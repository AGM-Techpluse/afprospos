<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\DecideDiagnosisFeeCommand;
use Domain\Repair\Domain\Exceptions\InvalidDiagnosisFeeState;
use Domain\Repair\Domain\Repositories\RepairDiagnosisFeeRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * The manager approves the diagnosis fee (it can then be collected) or declines it
 * (it is not due and is never charged).
 */
final class DecideDiagnosisFeeHandler
{
    public function __construct(
        private readonly RepairDiagnosisFeeRepository $fees,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DecideDiagnosisFeeCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $fee = $this->fees->find($jobId) ?? throw InvalidDiagnosisFeeState::forJob($command->repairJobId, 'no fee has been set.');

            if ($command->approve) {
                $fee->approve($command->decidedByStaffId);
            } else {
                $fee->decline($command->decidedByStaffId);
            }

            $this->fees->save($fee);

            $this->audit->record(
                module: 'Repair',
                eventType: $command->approve ? 'RepairDiagnosisFeeApproved' : 'RepairDiagnosisFeeDeclined',
                actorStaffId: new StaffId($command->decidedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: ['decision' => 'pending'],
                afterState: ['decision' => $fee->decision(), 'amount_minor' => $fee->amountMinor()],
            );
        });
    }
}
