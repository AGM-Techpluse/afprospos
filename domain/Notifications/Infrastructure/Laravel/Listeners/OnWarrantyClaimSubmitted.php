<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Laravel\Listeners;

use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Warranty\Domain\Events\WarrantyClaimSubmitted;

/** WarrantyClaimSubmitted already carries customerId — no cross-module lookup needed. */
final class OnWarrantyClaimSubmitted
{
    public function __construct(private readonly RequestNotificationHandler $requestNotification) {}

    public function handle(WarrantyClaimSubmitted $event): void
    {
        $this->requestNotification->handle(new RequestNotificationCommand(
            eventType: 'WarrantyClaimSubmitted',
            sourceModule: 'Warranty',
            sourceId: $event->warrantyClaimId,
            recipientType: 'customer',
            recipientId: $event->customerId,
            category: 'transactional',
            payload: [],
        ));
    }
}
