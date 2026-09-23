<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\RecordDiagnosisCommand;
use Domain\Repair\Domain\Entities\RepairDiagnosis;
use Domain\Repair\Domain\Repositories\RepairDiagnosisRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** REP-BR-29: records a single component test observation only — see RecordDiagnosisConclusionHandler for the actual diagnosis (root cause + outcome). */
final class RecordDiagnosisHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly RepairDiagnosisRepository $diagnoses,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RecordDiagnosisCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $actor = new StaffId($command->diagnosedByStaffId);

            if ($job->status() === 'received') {
                $job->startDiagnosis();
                $this->repairJobs->save($job);
            }

            $diagnosis = RepairDiagnosis::record(
                $jobId,
                $command->component,
                $command->condition,
                $command->notes,
                $actor,
            );
            $this->diagnoses->save($diagnosis);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairDiagnosisRecorded',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: null,
                afterState: [
                    'component' => $command->component,
                    'condition' => $command->condition,
                ],
            );
        });
    }
}
