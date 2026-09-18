<?php

declare(strict_types=1);

namespace Domain\Notifications\Domain\Repositories;

use Domain\Notifications\Domain\Entities\NotificationEvent;
use Domain\Notifications\Domain\ValueObjects\NotificationEventId;

interface NotificationEventRepository
{
    public function get(NotificationEventId $id): NotificationEvent;

    public function lockForUpdate(NotificationEventId $id): NotificationEvent;

    public function save(NotificationEvent $notificationEvent): NotificationEventId;

    /**
     * Events still `queued` whose delivery attempt count is below the
     * configured max — the safety net for jobs lost at the queue level
     * (`RetryFailedNotifications`), not the primary retry path (that's
     * `DispatchNotificationJob`'s own backoff).
     *
     * @return NotificationEventId[]
     */
    public function findRetryable(int $maxAttempts): array;

    /**
     * @return array{data: array<int, array<string, mixed>>, unread_count: int}
     */
    public function paginateForCustomer(int $customerId, int $limit = 10): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentFailedExhausted(int $limit = 20): array;
}
