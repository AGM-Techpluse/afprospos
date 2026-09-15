<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Commands\CreateCollectionCaseCommand;
use Domain\Collection\Application\Contracts\CollectionCaseCreator;
use Domain\Repair\Application\Commands\CompleteRepairCommand;
use Domain\Repair\Domain\Events\RepairCompleted;
use Domain\Repair\Domain\Events\RepairReadyForCollection;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Event;

final class CompleteRepairHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly CollectionCaseCreator $collectionCases,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CompleteRepairCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $actor = new StaffId($command->completedByStaffId);

            $job->complete($command->financialStatus);
            $this->repairJobs->save($job);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairCompleted',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: null,
                afterState: ['financial_status' => $command->financialStatus],
            );

            Event::dispatch(new RepairCompleted($jobId->value, $command->financialStatus, CarbonImmutable::now()));

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
