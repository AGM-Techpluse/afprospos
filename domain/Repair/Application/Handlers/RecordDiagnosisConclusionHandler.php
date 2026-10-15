<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Commands\CreateCollectionCaseCommand;
use Domain\Collection\Application\Contracts\CollectionCaseCreator;
use Domain\Repair\Application\Commands\RecordDiagnosisConclusionCommand;
use Domain\Repair\Domain\Entities\DiagnosisConclusion;
use Domain\Repair\Domain\Events\RepairDiagnosed;
use Domain\Repair\Domain\Events\RepairReadyForCollection;
use Domain\Repair\Domain\Repositories\DiagnosisConclusionRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Event;

/** REP-BR-29: the genuine diagnosis step — takes over what RecordDiagnosisHandler used to do on finalization, now split out. */
final class RecordDiagnosisConclusionHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly DiagnosisConclusionRepository $conclusions,
        private readonly CollectionCaseCreator $collectionCases,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RecordDiagnosisConclusionCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $actor = new StaffId($command->diagnosedByStaffId);

            // A repairable outcome is only saved here; the job moves on when staff send it to quote & authorization.
            if ($command->outcome !== 'repairable') {
                $job->recordDiagnosisOutcome($command->outcome);
            }

            $this->repairJobs->save($job);

            $conclusion = DiagnosisConclusion::record($jobId, $command->rootCause, $command->outcome, $actor);
            $this->conclusions->save($conclusion);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairDiagnosisConcluded',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: null,
                afterState: ['root_cause' => $command->rootCause, 'outcome' => $command->outcome],
            );

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
