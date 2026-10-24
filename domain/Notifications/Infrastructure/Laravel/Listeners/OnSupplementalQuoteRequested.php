<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Laravel\Listeners;

use Domain\Identity\Application\Contracts\StaffLookup;
use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Repair\Domain\Events\SupplementalQuoteRequested;

/** Mirrors OnRepairDownPaymentApprovalRequested — everyone who can decide this (same trust tier as the original quote authorization) is notified. */
final class OnSupplementalQuoteRequested
{
    public function __construct(
        private readonly StaffLookup $staff,
        private readonly RequestNotificationHandler $requestNotification,
    ) {}

    public function handle(SupplementalQuoteRequested $event): void
    {
        $approvers = $this->staff->findByPermission('repairs.authorize');

        foreach ($approvers as $approver) {
            $this->requestNotification->handle(new RequestNotificationCommand(
                eventType: 'SupplementalQuoteRequested',
                sourceModule: 'Repair',
                sourceId: $event->repairJobId,
                recipientType: 'staff',
                recipientId: $approver['id'],
                category: 'operational',
                payload: ['shop_id' => $event->shopId],
            ));
        }
    }
}
