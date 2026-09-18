<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use Domain\Notifications\Application\Commands\MarkNotificationReadCommand;
use Domain\Notifications\Application\Handlers\MarkNotificationReadHandler;
use Domain\Notifications\Domain\Exceptions\NotificationDoesNotBelongToCustomer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class NotificationController
{
    public function __construct(private readonly MarkNotificationReadHandler $markRead) {}

    public function markRead(Request $request, int $notification): RedirectResponse
    {
        try {
            $this->markRead->handle(new MarkNotificationReadCommand(
                notificationEventId: $notification,
                customerId: $request->user('customer')->id,
            ));
        } catch (NotificationDoesNotBelongToCustomer $exception) {
            throw ValidationException::withMessages(['notification' => $exception->getMessage()]);
        }

        return back();
    }
}
