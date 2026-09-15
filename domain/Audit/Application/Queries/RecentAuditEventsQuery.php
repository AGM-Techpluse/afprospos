<?php

declare(strict_types=1);

namespace Domain\Audit\Application\Queries;

use Domain\Audit\Infrastructure\Persistence\Eloquent\AuditLogRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;

/** Backs the Admin Dashboard's login-activity feed — a thin read over the append-only audit_logs table. */
final class RecentAuditEventsQuery
{
    /** @return array<int, array<string, mixed>> */
    public function byEventType(string $eventType, int $limit = 10): array
    {
        $events = AuditLogRecord::query()
            ->where('event_type', $eventType)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $staffNames = StaffRecord::query()
            ->whereIn('id', $events->pluck('actor_staff_id')->filter()->all())
            ->pluck('name', 'id');

        return $events->map(static fn (AuditLogRecord $event): array => [
            'id' => $event->id,
            'actor_staff_id' => $event->actor_staff_id,
            'actor_name' => $event->actor_staff_id !== null ? ($staffNames[$event->actor_staff_id] ?? null) : null,
            'created_at' => $event->created_at->toIso8601String(),
        ])->all();
    }
}
