<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Commands\RecordCollectionNotifiedCommand;
use Domain\Collection\Domain\Entities\CollectionCaseEvent;
use Domain\Collection\Domain\Repositories\CollectionCaseEventRepository;
use Domain\Collection\Domain\Repositories\CollectionCaseRepository;
use Domain\Collection\Domain\ValueObjects\CollectionCaseId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Manual only — never dispatched automatically (Phase 6 scope decision: real delivery is Phase 8's job). */
final class RecordCollectionNotifiedHandler
{
    public function __construct(
        private readonly CollectionCaseRepository $cases,
        private readonly CollectionCaseEventRepository $events,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RecordCollectionNotifiedCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new CollectionCaseId($command->collectionCaseId);
            $this->cases->get($id); // confirms the case exists before writing an event against it
            $actor = new StaffId($command->notifiedByStaffId);

            $this->events->save(CollectionCaseEvent::record($id, 'notified', $actor, ['note' => $command->note]));

            $this->audit->record(
                module: 'Collection',
                eventType: 'CollectionCustomerNotified',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'collection_case',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['note' => $command->note],
            );
        });
    }
}
