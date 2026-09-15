<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Commands\RecordCollectionOverrideCommand;
use Domain\Collection\Domain\Entities\CollectionCaseEvent;
use Domain\Collection\Domain\Repositories\CollectionCaseEventRepository;
use Domain\Collection\Domain\Repositories\CollectionCaseRepository;
use Domain\Collection\Domain\ValueObjects\CollectionCaseId;
use Domain\Repair\Application\Contracts\RepairFinancialStatusQuery;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * Implementation Plan Phase 6 exit criterion: the override is recorded
 * (audited, with actor + reason) BEFORE a release is ever attempted —
 * two commands in sequence, never folded into one, so the audit event
 * always exists first. `financial_status_at_override` (not a computed
 * minor balance) is recorded because RepairFinancialStatusQuery only
 * publishes the status string, not a running ledger total — expanding
 * that contract is deferred until a real need for it exists.
 */
final class RecordCollectionOverrideHandler
{
    public function __construct(
        private readonly CollectionCaseRepository $cases,
        private readonly CollectionCaseEventRepository $events,
        private readonly RepairFinancialStatusQuery $repairFinancialStatus,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RecordCollectionOverrideCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new CollectionCaseId($command->collectionCaseId);
            $case = $this->cases->lockForUpdate($id);
            $actor = new StaffId($command->overriddenByStaffId);

            $financialStatus = $case->sourceType() === 'repair_job'
                ? $this->repairFinancialStatus->financialStatusFor($case->sourceId())
                : 'unknown';

            $case->recordOverrideReason($command->reason);
            $this->cases->save($case);

            $detail = [
                'overridden_by_staff_id' => $command->overriddenByStaffId,
                'reason' => $command->reason,
                'financial_status_at_override' => $financialStatus,
            ];

            $this->events->save(CollectionCaseEvent::record($id, 'administrative_resolution', $actor, $detail));

            $this->audit->record(
                module: 'Collection',
                eventType: 'CollectionReleaseOverrideRecorded',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'collection_case',
                subjectId: $id->value,
                beforeState: null,
                afterState: $detail,
            );
        });
    }
}
