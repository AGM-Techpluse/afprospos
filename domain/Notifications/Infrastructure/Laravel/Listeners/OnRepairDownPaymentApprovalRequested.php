<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Laravel\Listeners;

use Domain\Identity\Application\Contracts\StaffLookup;
use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Repair\Domain\Events\RepairDownPaymentApprovalRequested;

/** Mirrors OnRepairPaymentApprovalRequested — every staff member who can decide this (Shop Owner or a per-shop Manager) is notified. */
final class OnRepairDownPaymentApprovalRequested
{
    public function __construct(
        private readonly StaffLookup $staff,
        private readonly RequestNotificationHandler $requestNotification,
    ) {}

    public function handle(RepairDownPaymentApprovalRequested $event): void
    {
        $approvers = $this->staff->findByPermission('repairs.approve-payment');

        foreach ($approvers as $approver) {
            $this->requestNotification->handle(new RequestNotificationCommand(
                eventType: 'RepairDownPaymentApprovalRequested',
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
