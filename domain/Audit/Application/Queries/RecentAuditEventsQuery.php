<?php

declare(strict_types=1);

namespace Domain\Audit\Application\Queries;

use Domain\Audit\Infrastructure\Persistence\Eloquent\AuditLogRecord;
use Domain\RBAC\Application\Queries\StaffShopGrantsQuery;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;

/** Backs the Admin Dashboard's login-activity feed — a thin read over the append-only audit_logs table. */
final class RecentAuditEventsQuery
{
    public function __construct(private readonly StaffShopGrantsQuery $shopGrants) {}

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

    /**
     * Login/logout side by side, newest first. Shop is resolved from the
     * actor's *current* shop grants at read time, not snapshotted at the
     * moment of login — a staff member's shop assignment isn't part of
     * what happened at that instant, and the active shop isn't even
     * chosen yet when AuthenticateStaffHandler runs (EnsureActiveShop-
     * SelectedHandler resolves it afterward, on a later request).
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentLoginActivity(int $limit = 10): array
    {
        $events = AuditLogRecord::query()
            ->whereIn('event_type', ['StaffLoggedIn', 'StaffLoggedOut'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $staffIds = $events->pluck('actor_staff_id')->filter()->unique()->values()->all();

        $staffNames = StaffRecord::query()->whereIn('id', $staffIds)->pluck('name', 'id');
        $shopNamesByStaffId = $this->resolveShopNames($staffIds);

        return $events->map(static fn (AuditLogRecord $event): array => [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'actor_staff_id' => $event->actor_staff_id,
            'actor_name' => $event->actor_staff_id !== null ? ($staffNames[$event->actor_staff_id] ?? null) : null,
            'ip_address' => $event->context['ip_address'] ?? null,
            'shop_names' => $event->actor_staff_id !== null ? ($shopNamesByStaffId[$event->actor_staff_id] ?? []) : [],
            'created_at' => $event->created_at->toIso8601String(),
        ])->all();
    }

    /**
     * @param  int[]  $staffIds
     * @return array<int, array<int, string>>
     */
    private function resolveShopNames(array $staffIds): array
    {
        $result = [];

        foreach ($staffIds as $staffId) {
            $shopIds = $this->shopGrants->activeShopIdsForStaff($staffId);

            // No explicit grants is how Shop Owner is modeled (the role
            // bypasses per-shop grants entirely) — "All shops" is the
            // correct read for that case, not a data gap.
            $result[$staffId] = $shopIds === []
                ? ['All shops']
                : ShopRecord::query()->whereIn('id', $shopIds)->orderBy('name')->pluck('name')->all();
        }

        return $result;
    }
}
