<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Laravel\Listeners;

use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Domain\Events\CollectionCaseResolved;
use Domain\Repair\Domain\Events\RepairCompleted;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Illuminate\Support\Facades\Event;

/**
 * A repair is only Completed once its device has been released to the customer.
 * Collection releases the device; this marks the repair done in the same transaction.
 */
final class OnCollectionCaseResolved
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly AuditWriter $audit,
    ) {}

    public function handle(CollectionCaseResolved $event): void
    {
        if ($event->sourceType !== 'repair_job' || $event->sourceId === null) {
            return;
        }

        $jobId = new RepairJobId($event->sourceId);
        $job = $this->repairJobs->lockForUpdate($jobId);

        // Already completed (e.g. an earlier release): nothing more to do.
        if ($job->status() === 'completed') {
            return;
        }

        $job->markCollected();
        $this->repairJobs->save($job);

        // The customer hears the repair is complete once the device is actually in their hands.
        Event::dispatch(new RepairCompleted($jobId->value, CarbonImmutable::now()));

        $this->audit->record(
            module: 'Repair',
            eventType: 'RepairCollected',
            actorStaffId: null,
            actorRoleSnapshot: null,
            subjectType: 'repair_job',
            subjectId: $jobId->value,
            beforeState: ['status' => 'handover'],
            afterState: ['status' => 'completed', 'collection_case_id' => $event->collectionCaseId],
        );
    }
}
