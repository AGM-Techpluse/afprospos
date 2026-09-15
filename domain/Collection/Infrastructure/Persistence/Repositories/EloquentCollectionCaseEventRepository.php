<?php

declare(strict_types=1);

namespace Domain\Collection\Infrastructure\Persistence\Repositories;

use Domain\Collection\Domain\Entities\CollectionCaseEvent;
use Domain\Collection\Domain\Repositories\CollectionCaseEventRepository;
use Domain\Collection\Domain\ValueObjects\CollectionCaseEventId;
use Domain\Collection\Domain\ValueObjects\CollectionCaseId;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseEventRecord;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class EloquentCollectionCaseEventRepository implements CollectionCaseEventRepository
{
    public function save(CollectionCaseEvent $event): void
    {
        CollectionCaseEventRecord::query()->create([
            'collection_case_id' => $event->collectionCaseId()->value,
            'event_type' => $event->eventType(),
            'actor_staff_id' => $event->actorStaffId()?->value,
            'detail' => $event->detail(),
        ]);
    }

    public function findByCase(CollectionCaseId $collectionCaseId): array
    {
        return CollectionCaseEventRecord::query()
            ->where('collection_case_id', $collectionCaseId->value)
            ->orderBy('created_at')
            ->get()
            ->map(fn (CollectionCaseEventRecord $record): CollectionCaseEvent => CollectionCaseEvent::reconstitute(
                new CollectionCaseEventId($record->id),
                $collectionCaseId,
                $record->event_type,
                $record->actor_staff_id !== null ? new StaffId($record->actor_staff_id) : null,
                $record->detail,
            ))
            ->all();
    }
}
