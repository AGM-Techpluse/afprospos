<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Persistence\Repositories;

use Domain\Notifications\Domain\Entities\NotificationEvent;
use Domain\Notifications\Domain\Repositories\NotificationEventRepository;
use Domain\Notifications\Domain\ValueObjects\NotificationEventId;
use Domain\Notifications\Infrastructure\Persistence\Eloquent\NotificationEventRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Illuminate\Support\Collection;

final class EloquentNotificationEventRepository implements NotificationEventRepository
{
    public function get(NotificationEventId $id): NotificationEvent
    {
        return $this->toDomain(NotificationEventRecord::query()->findOrFail($id->value));
    }

    public function lockForUpdate(NotificationEventId $id): NotificationEvent
    {
        $record = NotificationEventRecord::query()->whereKey($id->value)->lockForUpdate()->firstOrFail();

        return $this->toDomain($record);
    }

    public function save(NotificationEvent $notificationEvent): NotificationEventId
    {
        $attributes = [
            'event_type' => $notificationEvent->eventType(),
            'source_module' => $notificationEvent->sourceModule(),
            'source_id' => $notificationEvent->sourceId(),
            'recipient_type' => $notificationEvent->recipientType(),
            'recipient_id' => $notificationEvent->recipientId(),
            'category' => $notificationEvent->category(),
            'payload' => $notificationEvent->payload(),
            'status' => $notificationEvent->status(),
            'read_at' => $notificationEvent->readAt(),
        ];

        if ($notificationEvent->id() === null) {
            $record = NotificationEventRecord::query()->create($attributes);
        } else {
            $record = NotificationEventRecord::query()->findOrFail($notificationEvent->id()->value);
            $record->update($attributes);
        }

        return new NotificationEventId($record->id);
    }

    public function findRetryable(int $maxAttempts): array
    {
        return NotificationEventRecord::query()
            ->where('status', 'queued')
            ->whereRaw(
                '(SELECT COUNT(*) FROM notification_delivery_attempts WHERE notification_delivery_attempts.notification_event_id = notification_events.id) < ?',
                [$maxAttempts],
            )
            ->pluck('id')
            ->map(static fn (int $id): NotificationEventId => new NotificationEventId($id))
            ->all();
    }

    public function paginateForCustomer(int $customerId, int $limit = 10): array
    {
        $records = NotificationEventRecord::query()
            ->where('recipient_type', 'customer')
            ->where('recipient_id', $customerId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return [
            'data' => $records->map(fn (NotificationEventRecord $record): array => $this->toArray($record))->all(),
            'unread_count' => NotificationEventRecord::query()
                ->where('recipient_type', 'customer')
                ->where('recipient_id', $customerId)
                ->whereNull('read_at')
                ->count(),
        ];
    }

    public function paginateForStaff(int $staffId, int $limit = 10): array
    {
        $records = NotificationEventRecord::query()
            ->where('recipient_type', 'staff')
            ->where('recipient_id', $staffId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $shopIds = $records
            ->map(fn (NotificationEventRecord $record): mixed => $record->payload['shop_id'] ?? null)
            ->filter()
            ->unique()
            ->values();
        $shopNames = ShopRecord::query()->whereIn('id', $shopIds)->pluck('name', 'id');

        return [
            'data' => $records->map(fn (NotificationEventRecord $record): array => $this->toArray($record, $shopNames))->all(),
            'unread_count' => NotificationEventRecord::query()
                ->where('recipient_type', 'staff')
                ->where('recipient_id', $staffId)
                ->whereNull('read_at')
                ->count(),
        ];
    }

    public function recentFailedExhausted(int $limit = 20): array
    {
        return NotificationEventRecord::query()
            ->where('status', 'failed_exhausted')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (NotificationEventRecord $record): array => $this->toArray($record))
            ->all();
    }

    /** @return array<string, mixed> */
    /** @param  Collection<int|string, string>  $shopNames */
    private function toArray(NotificationEventRecord $record, $shopNames = null): array
    {
        $payload = $record->payload;

        if ($shopNames !== null && isset($payload['shop_id'])) {
            $payload['shop_name'] = $shopNames->get($payload['shop_id']);
        }

        return [
            'id' => $record->id,
            'event_type' => $record->event_type,
            'source_module' => $record->source_module,
            'source_id' => $record->source_id,
            'category' => $record->category,
            'payload' => $payload,
            'status' => $record->status,
            'read_at' => $record->read_at?->toIso8601String(),
            'created_at' => $record->created_at->toIso8601String(),
        ];
    }

    private function toDomain(NotificationEventRecord $record): NotificationEvent
    {
        return NotificationEvent::reconstitute(
            new NotificationEventId($record->id),
            $record->event_type,
            $record->source_module,
            $record->source_id,
            $record->recipient_type,
            $record->recipient_id,
            $record->category,
            $record->payload ?? [],
            $record->status,
            $record->read_at?->toDateTimeImmutable(),
        );
    }
}
