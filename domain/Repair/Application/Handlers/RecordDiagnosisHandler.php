<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Commands\CreateCollectionCaseCommand;
use Domain\Collection\Application\Contracts\CollectionCaseCreator;
use Domain\Repair\Application\Commands\RecordDiagnosisCommand;
use Domain\Repair\Domain\Entities\RepairDiagnosis;
use Domain\Repair\Domain\Events\RepairDiagnosed;
use Domain\Repair\Domain\Events\RepairReadyForCollection;
use Domain\Repair\Domain\Repositories\RepairDiagnosisRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Event;

final class RecordDiagnosisHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly RepairDiagnosisRepository $diagnoses,
        private readonly CollectionCaseCreator $collectionCases,
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
            }

            $diagnosis = RepairDiagnosis::record(
                $jobId,
                $command->component,
                $command->condition,
                $command->notes,
                $command->outcome,
                $actor,
            );
            $this->diagnoses->save($diagnosis);

            if ($command->outcome !== null) {
                $job->recordDiagnosisOutcome($command->outcome);
            }

            $this->repairJobs->save($job);

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
                    'outcome' => $command->outcome,
                ],
            );

            if ($command->outcome === null) {
                return;
            }

            Event::dispatch(new RepairDiagnosed($jobId->value, $command->outcome, CarbonImmutable::now()));

            if ($job->status() !== 'unrepairable') {
                return;
            }

            $collectionCaseId = $this->collectionCases->create(new CreateCollectionCaseCommand(
                sourceType: 'repair_job',
                sourceId: $jobId->value,
                shopId: $job->shopId()->value,
                originatingShopId: $job->shopId()->value,
                context: 'ready_for_return',
                deadlineDays: (int) config('afprospos.collection.default_deadline_days'),
            ));

            Event::dispatch(new RepairReadyForCollection($jobId->value, $collectionCaseId, CarbonImmutable::now()));
        });
    }
}
