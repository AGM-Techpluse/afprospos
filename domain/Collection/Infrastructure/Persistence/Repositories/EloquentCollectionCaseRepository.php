<?php

declare(strict_types=1);

namespace Domain\Collection\Infrastructure\Persistence\Repositories;

use Domain\Collection\Domain\Entities\CollectionCase;
use Domain\Collection\Domain\Repositories\CollectionCaseRepository;
use Domain\Collection\Domain\ValueObjects\CollectionCaseId;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Illuminate\Support\Carbon;

final class EloquentCollectionCaseRepository implements CollectionCaseRepository
{
    public function get(CollectionCaseId $id): CollectionCase
    {
        return $this->toDomain(CollectionCaseRecord::query()->findOrFail($id->value));
    }

    public function lockForUpdate(CollectionCaseId $id): CollectionCase
    {
        $record = CollectionCaseRecord::query()->whereKey($id->value)->lockForUpdate()->firstOrFail();

        return $this->toDomain($record);
    }

    public function save(CollectionCase $case): CollectionCaseId
    {
        $attributes = [
            'source_type' => $case->sourceType(),
            'source_id' => $case->sourceId(),
            'shop_id' => $case->shopId(),
            'originating_shop_id' => $case->originatingShopId(),
            'context' => $case->context(),
            'status' => $case->status(),
            'collection_deadline_at' => $case->collectionDeadlineAt(),
            'abandonment_threshold_at' => $case->abandonmentThresholdAt(),
            'storage_fee_policy_snapshot' => $case->storageFeePolicySnapshot(),
            'accrued_storage_fee_minor' => $case->accruedStorageFeeMinor(),
            'shop_override_reason' => $case->shopOverrideReason(),
        ];

        if ($case->id() === null) {
            $record = CollectionCaseRecord::query()->create($attributes);
        } else {
            $record = CollectionCaseRecord::query()->findOrFail($case->id()->value);
            $record->update($attributes);
        }

        return new CollectionCaseId($record->id);
    }

    public function findBySource(string $sourceType, int $sourceId): array
    {
        return CollectionCaseRecord::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->get()
            ->map(fn (CollectionCaseRecord $record): CollectionCase => $this->toDomain($record))
            ->all();
    }

    public function findDueForOverdue(int $limit): array
    {
        return CollectionCaseRecord::query()
            ->where('status', 'pending')
            ->where('collection_deadline_at', '<=', Carbon::now())
            ->orderBy('collection_deadline_at')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    public function findDueForAbandonment(int $limit): array
    {
        return CollectionCaseRecord::query()
            ->where('status', 'overdue')
            ->whereNotNull('abandonment_threshold_at')
            ->where('abandonment_threshold_at', '<=', Carbon::now())
            ->orderBy('abandonment_threshold_at')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    public function findPendingOrOverdue(int $limit): array
    {
        return CollectionCaseRecord::query()
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('collection_deadline_at')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    private function toDomain(CollectionCaseRecord $record): CollectionCase
    {
        return CollectionCase::reconstitute(
            new CollectionCaseId($record->id),
            $record->source_type,
            $record->source_id,
            $record->shop_id,
            $record->originating_shop_id,
            $record->context,
            $record->status,
            $record->collection_deadline_at->toDateTimeImmutable(),
            $record->abandonment_threshold_at?->toDateTimeImmutable(),
            $record->storage_fee_policy_snapshot,
            $record->accrued_storage_fee_minor,
            $record->shop_override_reason,
        );
    }
}
