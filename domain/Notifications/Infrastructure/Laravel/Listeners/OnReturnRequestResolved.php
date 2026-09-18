<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Laravel\Listeners;

use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Warranty\Domain\Events\ReturnRequestResolved;

final class OnReturnRequestResolved
{
    public function __construct(private readonly RequestNotificationHandler $requestNotification) {}

    public function handle(ReturnRequestResolved $event): void
    {
        $this->requestNotification->handle(new RequestNotificationCommand(
            eventType: 'ReturnRequestResolved',
            sourceModule: 'Warranty',
            sourceId: $event->returnRequestId,
            recipientType: 'customer',
            recipientId: $event->customerId,
            category: 'transactional',
            payload: [],
        ));
    }
}
