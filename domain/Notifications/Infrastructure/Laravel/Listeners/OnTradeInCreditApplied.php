<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Laravel\Listeners;

use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Warranty\Domain\Events\TradeInCreditApplied;

final class OnTradeInCreditApplied
{
    public function __construct(private readonly RequestNotificationHandler $requestNotification) {}

    public function handle(TradeInCreditApplied $event): void
    {
        $this->requestNotification->handle(new RequestNotificationCommand(
            eventType: 'TradeInCreditApplied',
            sourceModule: 'Warranty',
            sourceId: $event->tradeInAssessmentId,
            recipientType: 'customer',
            recipientId: $event->customerId,
            category: 'transactional',
            payload: [],
        ));
    }
}
