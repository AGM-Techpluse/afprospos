<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Domain\Notifications\Application\Commands\MarkAllStaffNotificationsReadCommand;
use Domain\Notifications\Application\Commands\MarkStaffNotificationReadCommand;
use Domain\Notifications\Application\Handlers\MarkAllStaffNotificationsReadHandler;
use Domain\Notifications\Application\Handlers\MarkStaffNotificationReadHandler;
use Domain\Notifications\Application\Queries\StaffNotificationInboxQuery;
use Domain\Notifications\Domain\Exceptions\NotificationDoesNotBelongToStaff;
use Domain\Shared\Application\DTOs\ActorContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class NotificationController
{
    public function __construct(
        private readonly MarkStaffNotificationReadHandler $markRead,
        private readonly MarkAllStaffNotificationsReadHandler $markAllRead,
        private readonly StaffNotificationInboxQuery $notifications,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $this->notifications->history($actor->staffId->value, $request->integer('page', 1)),
        ]);
    }

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

    public function markAllRead(): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->markAllRead->handle(new MarkAllStaffNotificationsReadCommand(staffId: $actor->staffId->value));

        return back();
    }
}
