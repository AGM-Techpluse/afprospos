<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Notifications\Application\Commands\MarkAllStaffNotificationsReadCommand;
use Domain\Notifications\Domain\Repositories\NotificationEventRepository;
use Illuminate\Support\Facades\Date;

final class MarkAllStaffNotificationsReadHandler
{
    public function __construct(
        private readonly NotificationEventRepository $notificationEvents,
        private readonly Atomic $atomic,
    ) {}

    public function handle(MarkAllStaffNotificationsReadCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $this->notificationEvents->markAllReadForStaff($command->staffId, Date::now()->toDateTimeImmutable());
        });
    }
}
