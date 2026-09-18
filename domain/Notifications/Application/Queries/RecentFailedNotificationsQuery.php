<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Queries;

use Domain\Notifications\Domain\Repositories\NotificationEventRepository;

/** Backs the Admin Settings > Notifications diagnostics page — NOTIF-BR-12's "retained for admin/manual intervention." */
final class RecentFailedNotificationsQuery
{
    public function __construct(private readonly NotificationEventRepository $notificationEvents) {}

    /** @return array<int, array<string, mixed>> */
    public function recent(int $limit = 20): array
    {
        return $this->notificationEvents->recentFailedExhausted($limit);
    }
}
