<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Commands\ExtendCollectionDeadlineCommand;
use Domain\Collection\Domain\Entities\CollectionCaseEvent;
use Domain\Collection\Domain\Repositories\CollectionCaseEventRepository;
use Domain\Collection\Domain\Repositories\CollectionCaseRepository;
use Domain\Collection\Domain\ValueObjects\CollectionCaseId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class ExtendCollectionDeadlineHandler
{
    public function __construct(
        private readonly CollectionCaseRepository $cases,
        private readonly CollectionCaseEventRepository $events,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ExtendCollectionDeadlineCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new CollectionCaseId($command->collectionCaseId);
            $case = $this->cases->lockForUpdate($id);
            $actor = new StaffId($command->extendedByStaffId);

            $case->extendDeadline($command->newDeadlineAt, $command->newAbandonmentThresholdAt);
            $this->cases->save($case);

            $this->events->save(CollectionCaseEvent::record($id, 'extended', $actor, [
                'new_deadline_at' => $command->newDeadlineAt->toDateTimeString(),
                'reason' => $command->reason,
            ]));

            $this->audit->record(
                module: 'Collection',
                eventType: 'CollectionDeadlineExtended',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'collection_case',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['new_deadline_at' => $command->newDeadlineAt->toDateTimeString(), 'reason' => $command->reason],
            );
        });
    }
}
