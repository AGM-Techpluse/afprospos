<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Commands\CreateCollectionCaseCommand;
use Domain\Collection\Application\Contracts\CollectionCaseCreator;
use Domain\Repair\Application\Commands\RecordQualityCheckCommand;
use Domain\Repair\Application\Queries\QcFailPolicyQuery;
use Domain\Repair\Application\Services\ReleaseRepairPartsService;
use Domain\Repair\Domain\Events\RepairReadyForCollection;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\Repositories\RepairQcCheckRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Event;

/**
 * Records a quality check and moves the job on:
 * - passed, or not testable (with a note): completed, collection case opened, device ready for handover;
 * - failed: back to diagnosis, or cancelled, as the admin's policy says.
 */
final class RecordQualityCheckHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly RepairQcCheckRepository $qcChecks,
        private readonly QcFailPolicyQuery $failPolicy,
        private readonly ReleaseRepairPartsService $releaseParts,
        private readonly CollectionCaseCreator $collectionCases,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RecordQualityCheckCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);

            $this->qcChecks->record($command->repairJobId, $command->outcome, $command->notes, $command->checkedByStaffId);

            if ($command->outcome === 'failed') {
                if ($this->failPolicy->current() === 'cancel') {
                    $job->cancel('Quality check failed');
                    $this->releaseParts->releaseOutstanding($jobId, $job->shopId()->value);
                } else {
                    $job->returnToDiagnosisAfterFailedQc();
                }
            } else {
                $job->passQualityCheck();
            }

            $this->repairJobs->save($job);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairQualityChecked',
                actorStaffId: new StaffId($command->checkedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: null,
                afterState: ['outcome' => $command->outcome, 'notes' => $command->notes, 'status' => $job->status()],
            );

            if ($command->outcome === 'failed') {
                return;
            }

            $collectionCaseId = $this->collectionCases->create(new CreateCollectionCaseCommand(
                sourceType: 'repair_job',
                sourceId: $jobId->value,
                shopId: $job->shopId()->value,
                originatingShopId: $job->shopId()->value,
                context: 'ready_for_collection',
                deadlineDays: (int) config('afprospos.collection.default_deadline_days'),
            ));

            Event::dispatch(new RepairReadyForCollection($jobId->value, $collectionCaseId, CarbonImmutable::now()));
        });
    }
}
