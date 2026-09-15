<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Commands\MarkCollectionOverdueCommand;
use Domain\Collection\Domain\Entities\CollectionCaseEvent;
use Domain\Collection\Domain\Repositories\CollectionCaseEventRepository;
use Domain\Collection\Domain\Repositories\CollectionCaseRepository;
use Domain\Collection\Domain\ValueObjects\CollectionCaseId;

final class MarkCollectionOverdueHandler
{
    public function __construct(
        private readonly CollectionCaseRepository $cases,
        private readonly CollectionCaseEventRepository $events,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(MarkCollectionOverdueCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new CollectionCaseId($command->collectionCaseId);
            $case = $this->cases->lockForUpdate($id);

            $case->markOverdue();
            $this->cases->save($case);

            $this->events->save(CollectionCaseEvent::record($id, 'marked_overdue', null, []));

            $this->audit->record(
                module: 'Collection',
                eventType: 'CollectionCaseMarkedOverdue',
                actorStaffId: null,
                actorRoleSnapshot: null,
                subjectType: 'collection_case',
                subjectId: $id->value,
                beforeState: null,
                afterState: null,
            );
        });
    }
}
