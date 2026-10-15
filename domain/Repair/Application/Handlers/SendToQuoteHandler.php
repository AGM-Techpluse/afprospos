<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\SendToQuoteCommand;
use Domain\Repair\Domain\Exceptions\DiagnosisNotRepairable;
use Domain\Repair\Domain\Repositories\DiagnosisConclusionRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Frontdesk sends a saved, repairable diagnosis to the manager for the quote and authorization. */
final class SendToQuoteHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly DiagnosisConclusionRepository $conclusions,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(SendToQuoteCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);

            if (! $this->conclusions->hasRepairableConclusion($jobId)) {
                throw DiagnosisNotRepairable::forJob($command->repairJobId);
            }

            $job = $this->repairJobs->lockForUpdate($jobId);
            $job->sendToQuote();
            $this->repairJobs->save($job);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairSentToQuote',
                actorStaffId: new StaffId($command->sentByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: ['status' => 'diagnosing'],
                afterState: ['status' => 'awaiting_authorization'],
            );
        });
    }
}
