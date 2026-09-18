<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Laravel\Listeners;

use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Repair\Application\Contracts\RepairJobLookup;
use Domain\Repair\Domain\Events\RepairReadyForCollection;

final class OnRepairReadyForCollection
{
    public function __construct(
        private readonly RepairJobLookup $repairJobs,
        private readonly RequestNotificationHandler $requestNotification,
    ) {}

    public function handle(RepairReadyForCollection $event): void
    {
        $repairJob = $this->repairJobs->find($event->repairJobId);

        if ($repairJob === null) {
            return;
        }

        $this->requestNotification->handle(new RequestNotificationCommand(
            eventType: 'RepairReadyForCollection',
            sourceModule: 'Repair',
            sourceId: $event->repairJobId,
            recipientType: 'customer',
            recipientId: $repairJob['customer_id'],
            category: 'transactional',
            payload: [
                'device_make' => $repairJob['device_make'],
                'device_model' => $repairJob['device_model'],
                'collection_case_id' => $event->collectionCaseId,
            ],
        ));
    }
}
