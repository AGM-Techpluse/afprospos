<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Notifications\Application\Commands\MarkNotificationReadCommand;
use Domain\Notifications\Domain\Exceptions\NotificationDoesNotBelongToCustomer;
use Domain\Notifications\Domain\Repositories\NotificationEventRepository;
use Domain\Notifications\Domain\ValueObjects\NotificationEventId;
use Illuminate\Support\Facades\Date;

final class MarkNotificationReadHandler
{
    public function __construct(
        private readonly NotificationEventRepository $notificationEvents,
        private readonly Atomic $atomic,
    ) {}

    public function handle(MarkNotificationReadCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new NotificationEventId($command->notificationEventId);
            $notificationEvent = $this->notificationEvents->lockForUpdate($id);

            if ($notificationEvent->recipientType() !== 'customer' || $notificationEvent->recipientId() !== $command->customerId) {
                throw NotificationDoesNotBelongToCustomer::forNotificationEvent($id->value);
            }

            $notificationEvent->markRead(Date::now()->toDateTimeImmutable());
            $this->notificationEvents->save($notificationEvent);
        });
    }
}
