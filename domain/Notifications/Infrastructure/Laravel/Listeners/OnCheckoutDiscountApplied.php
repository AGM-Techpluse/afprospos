<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Laravel\Listeners;

use Domain\Identity\Application\Contracts\StaffLookup;
use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Sales\Domain\Events\CheckoutDiscountApplied;

/** MKT-BR-15: every Shop Owner is notified so one of them can approve the discount before the checkout can be paid. */
final class OnCheckoutDiscountApplied
{
    public function __construct(
        private readonly StaffLookup $staff,
        private readonly RequestNotificationHandler $requestNotification,
    ) {}

    public function handle(CheckoutDiscountApplied $event): void
    {
        $owners = $this->staff->findByRole((string) config('afprospos.owner_role_name'));

        foreach ($owners as $owner) {
            $this->requestNotification->handle(new RequestNotificationCommand(
                eventType: 'CheckoutDiscountApplied',
                sourceModule: 'Sales',
                sourceId: $event->checkoutId,
                recipientType: 'staff',
                recipientId: $owner['id'],
                category: 'operational',
                payload: ['shop_id' => $event->shopId, 'amount_minor' => $event->amountMinor],
            ));
        }
    }
}
