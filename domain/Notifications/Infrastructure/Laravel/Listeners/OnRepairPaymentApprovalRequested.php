<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Laravel\Listeners;

use Domain\Identity\Application\Contracts\StaffLookup;
use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Repair\Domain\Events\RepairPaymentApprovalRequested;

/** REP-BR-28: every staff member who can decide this (Shop Owner or a per-shop Manager) is notified, resolved by permission rather than a hard-coded role name. */
final class OnRepairPaymentApprovalRequested
{
    public function __construct(
        private readonly StaffLookup $staff,
        private readonly RequestNotificationHandler $requestNotification,
    ) {}

    public function handle(RepairPaymentApprovalRequested $event): void
    {
        $approvers = $this->staff->findByPermission('repairs.approve-payment');

        foreach ($approvers as $approver) {
            $this->requestNotification->handle(new RequestNotificationCommand(
                eventType: 'RepairPaymentApprovalRequested',
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
