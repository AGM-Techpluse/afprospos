<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Domain\Notifications\Application\Commands\MarkStaffNotificationReadCommand;
use Domain\Notifications\Application\Handlers\MarkStaffNotificationReadHandler;
use Domain\Notifications\Domain\Exceptions\NotificationDoesNotBelongToStaff;
use Domain\Shared\Application\DTOs\ActorContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class NotificationController
{
    public function __construct(private readonly MarkStaffNotificationReadHandler $markRead) {}

    public function markRead(int $notification): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $this->markRead->handle(new MarkStaffNotificationReadCommand(
                notificationEventId: $notification,
                staffId: $actor->staffId->value,
            ));
        } catch (NotificationDoesNotBelongToStaff $exception) {
            throw ValidationException::withMessages(['notification' => $exception->getMessage()]);
        }

        return back();
    }
}
