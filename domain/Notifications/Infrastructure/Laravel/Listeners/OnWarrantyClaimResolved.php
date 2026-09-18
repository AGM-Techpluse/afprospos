<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Laravel\Listeners;

use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Warranty\Application\Contracts\WarrantyClaimLookup;
use Domain\Warranty\Domain\Events\WarrantyClaimResolved;

/** WarrantyClaimResolved doesn't carry customerId — resolved via the new, purely-additive WarrantyClaimLookup published contract (mirrors RepairJobLookup's role for Repair). */
final class OnWarrantyClaimResolved
{
    public function __construct(
        private readonly WarrantyClaimLookup $warrantyClaims,
        private readonly RequestNotificationHandler $requestNotification,
    ) {}

    public function handle(WarrantyClaimResolved $event): void
    {
        $claim = $this->warrantyClaims->find($event->warrantyClaimId);

        if ($claim === null) {
            return;
        }

        $this->requestNotification->handle(new RequestNotificationCommand(
            eventType: 'WarrantyClaimResolved',
            sourceModule: 'Warranty',
            sourceId: $event->warrantyClaimId,
            recipientType: 'customer',
            recipientId: $claim['customer_id'],
            category: 'transactional',
            payload: ['selected_remedy' => $event->selectedRemedy],
        ));
    }
}
