<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Commands\CreateCollectionCaseCommand;
use Domain\Collection\Application\Contracts\CollectionCaseCreator;
use Domain\Repair\Application\Commands\MarkRepairUnrepairableCommand;
use Domain\Repair\Domain\Events\RepairReadyForCollection;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Event;

/** The `failed_requires_resolution -> unrepairable` path — diagnosis already finalized, so this reuses the same collection-case-creation flow as the diagnosis-time `diagnosing -> unrepairable` edge. */
final class MarkRepairUnrepairableHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly CollectionCaseCreator $collectionCases,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(MarkRepairUnrepairableCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $actor = new StaffId($command->markedByStaffId);

            $job->markUnrepairable($command->settlementState);
            $this->repairJobs->save($job);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairMarkedUnrepairable',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: null,
                afterState: ['settlement_state' => $command->settlementState],
            );

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
