<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Queries;

use Domain\Notifications\Domain\Repositories\NotificationEventRepository;

/** Backs the shared Inertia `notifications` prop (HandleInertiaRequests) that feeds NotificationDropdown on every Customer page. */
final class CustomerNotificationInboxQuery
{
    public function __construct(private readonly NotificationEventRepository $notificationEvents) {}

    /** @return array{data: array<int, array<string, mixed>>, unread_count: int} */
    public function forCustomer(int $customerId, int $limit = 10): array
    {
        return $this->notificationEvents->paginateForCustomer($customerId, $limit);
    }
}
