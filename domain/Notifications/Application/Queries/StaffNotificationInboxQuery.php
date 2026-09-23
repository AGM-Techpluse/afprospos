<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Queries;

use Domain\Notifications\Domain\Repositories\NotificationEventRepository;

/** Backs the shared Inertia `staffNotifications` prop (HandleInertiaRequests) that feeds Admin/NotificationDropdown.tsx on every Admin page. */
final class StaffNotificationInboxQuery
{
    public function __construct(private readonly NotificationEventRepository $notificationEvents) {}

    /** @return array{data: array<int, array<string, mixed>>, unread_count: int} */
    public function forStaff(int $staffId, int $limit = 10): array
    {
        return $this->notificationEvents->paginateForStaff($staffId, $limit);
    }
}
