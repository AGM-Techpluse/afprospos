<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Laravel\Listeners;

use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Sales\Application\Contracts\SaleLookup;
use Domain\Sales\Domain\Events\SaleCompleted;

/** A walk-in sale (no registered customer) has nothing to notify — SaleCompleted::$customerId is null in that case. */
final class OnSaleCompleted
{
    public function __construct(
        private readonly SaleLookup $sales,
        private readonly RequestNotificationHandler $requestNotification,
    ) {}

    public function handle(SaleCompleted $event): void
    {
        if ($event->customerId === null) {
            return;
        }

        $sale = $this->sales->find($event->saleId);

        $this->requestNotification->handle(new RequestNotificationCommand(
            eventType: 'SaleCompleted',
            sourceModule: 'Sales',
            sourceId: $event->saleId,
            recipientType: 'customer',
            recipientId: $event->customerId,
            category: 'transactional',
            payload: ['invoice_number' => $sale['invoice_number'] ?? null],
        ));
    }
}
