<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Queries;

use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseEventRecord;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;

final class CollectionCaseDetailQuery
{
    /** @return array<string, mixed>|null */
    public function find(int $collectionCaseId): ?array
    {
        $case = CollectionCaseRecord::query()->find($collectionCaseId);

        if ($case === null) {
            return null;
        }

        $events = CollectionCaseEventRecord::query()
            ->where('collection_case_id', $collectionCaseId)
            ->orderByDesc('created_at')
            ->get()
            ->map(static fn (CollectionCaseEventRecord $event): array => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'actor_staff_id' => $event->actor_staff_id,
                'detail' => $event->detail,
                'created_at' => $event->created_at->toIso8601String(),
            ])->all();

        return [
            'id' => $case->id,
            'source_type' => $case->source_type,
            'source_id' => $case->source_id,
            'shop_id' => $case->shop_id,
            'originating_shop_id' => $case->originating_shop_id,
            'context' => $case->context,
            'status' => $case->status,
            'collection_deadline_at' => $case->collection_deadline_at->toIso8601String(),
            'abandonment_threshold_at' => $case->abandonment_threshold_at?->toIso8601String(),
            'storage_fee_policy_snapshot' => $case->storage_fee_policy_snapshot,
            'accrued_storage_fee_minor' => $case->accrued_storage_fee_minor,
            'shop_override_reason' => $case->shop_override_reason,
            'events' => $events,
        ];
    }
}
