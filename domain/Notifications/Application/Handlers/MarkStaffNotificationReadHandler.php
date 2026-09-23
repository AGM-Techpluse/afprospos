<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Notifications\Application\Commands\MarkStaffNotificationReadCommand;
use Domain\Notifications\Domain\Exceptions\NotificationDoesNotBelongToStaff;
use Domain\Notifications\Domain\Repositories\NotificationEventRepository;
use Domain\Notifications\Domain\ValueObjects\NotificationEventId;
use Illuminate\Support\Facades\Date;

final class MarkStaffNotificationReadHandler
{
    public function __construct(
        private readonly NotificationEventRepository $notificationEvents,
        private readonly Atomic $atomic,
    ) {}

    public function handle(MarkStaffNotificationReadCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new NotificationEventId($command->notificationEventId);
            $notificationEvent = $this->notificationEvents->lockForUpdate($id);

            if ($notificationEvent->recipientType() !== 'staff' || $notificationEvent->recipientId() !== $command->staffId) {
                throw NotificationDoesNotBelongToStaff::forNotificationEvent($id->value);
            }

            $notificationEvent->markRead(Date::now()->toDateTimeImmutable());
            $this->notificationEvents->save($notificationEvent);
        });
    }
}
