<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Laravel\Listeners;

use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Repair\Application\Contracts\RepairJobLookup;
use Domain\Repair\Domain\Events\RepairCompleted;

/** No changes to the Repair module — RepairCompleted already dispatches from CompleteRepairHandler; this only subscribes to it. */
final class OnRepairCompleted
{
    public function __construct(
        private readonly RepairJobLookup $repairJobs,
        private readonly RequestNotificationHandler $requestNotification,
    ) {}

    public function handle(RepairCompleted $event): void
    {
        $repairJob = $this->repairJobs->find($event->repairJobId);

        if ($repairJob === null) {
            return;
        }

        $this->requestNotification->handle(new RequestNotificationCommand(
            eventType: 'RepairCompleted',
            sourceModule: 'Repair',
            sourceId: $event->repairJobId,
            recipientType: 'customer',
            recipientId: $repairJob['customer_id'],
            category: 'transactional',
            payload: [
                'device_make' => $repairJob['device_make'],
                'device_model' => $repairJob['device_model'],
            ],
        ));
    }
}
