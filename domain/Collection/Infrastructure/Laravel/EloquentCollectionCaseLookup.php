<?php

declare(strict_types=1);

namespace Domain\Collection\Infrastructure\Laravel;

use Domain\Collection\Application\Contracts\CollectionCaseLookup;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1). */
final class EloquentCollectionCaseLookup implements CollectionCaseLookup
{
    public function findLatestBySource(string $sourceType, int $sourceId): ?array
    {
        $case = CollectionCaseRecord::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->latest('id')
            ->first();

        if ($case === null) {
            return null;
        }

        return [
            'id' => $case->id,
            'status' => $case->status,
            'context' => $case->context,
            'collection_deadline_at' => $case->collection_deadline_at->toIso8601String(),
        ];
    }
}
