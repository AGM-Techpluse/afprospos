<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Commands\ReleaseRepairDeviceCommand;
use Domain\Collection\Domain\Events\CollectionCaseResolved;
use Domain\Collection\Domain\Exceptions\ReleaseBlockedByOutstandingBalance;
use Domain\Collection\Domain\Repositories\CollectionCaseEventRepository;
use Domain\Collection\Domain\Repositories\CollectionCaseRepository;
use Domain\Collection\Domain\ValueObjects\CollectionCaseId;
use Domain\Repair\Application\Contracts\DeviceLockClearer;
use Domain\Repair\Application\Contracts\RepairFinancialStatusQuery;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Event;

/** Implementation Plan Phase 6 exit criterion: cannot release with an outstanding balance except through a previously-recorded, audited override (RecordCollectionOverrideCommand). */
final class ReleaseRepairDeviceHandler
{
    public function __construct(
        private readonly CollectionCaseRepository $cases,
        private readonly CollectionCaseEventRepository $events,
        private readonly RepairFinancialStatusQuery $repairFinancialStatus,
        private readonly DeviceLockClearer $deviceLockClearer,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ReleaseRepairDeviceCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new CollectionCaseId($command->collectionCaseId);
            $case = $this->cases->lockForUpdate($id);
            $actor = new StaffId($command->releasedByStaffId);

            $financialStatus = $case->sourceType() === 'repair_job'
                ? $this->repairFinancialStatus->financialStatusFor($case->sourceId())
                : 'fully_paid';

            $hasOverride = false;
            foreach ($this->events->findByCase($id) as $event) {
                if ($event->eventType() === 'administrative_resolution') {
                    $hasOverride = true;
                    break;
                }
            }

            if ($financialStatus !== 'fully_paid' && ! $hasOverride) {
                throw ReleaseBlockedByOutstandingBalance::forCase($id->value);
            }

            $case->resolve();
            $this->cases->save($case);

            if ($case->sourceType() === 'repair_job') {
                $this->deviceLockClearer->clear($case->sourceId(), $command->releasedByStaffId);
            }

            $this->audit->record(
                module: 'Collection',
                eventType: 'CollectionCaseResolved',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'collection_case',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['financial_status_at_release' => $financialStatus, 'via_override' => $hasOverride],
            );

            Event::dispatch(new CollectionCaseResolved($id->value, CarbonImmutable::now()));
        });
    }
}
