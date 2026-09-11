<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ReleaseInventoryCommand;
use Domain\Inventory\Application\Contracts\InventoryReservationService;
use Domain\Sales\Application\Commands\CancelCheckoutCommand;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;

/** Cashier-initiated cancellation — releases every item's reservation, mirrors ExpireCheckoutHandler minus the idempotency-by-clock concern. */
final class CancelCheckoutHandler
{
    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly InventoryReservationService $reservations,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CancelCheckoutCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $checkout = $this->checkouts->lockForUpdate(new CheckoutId($command->checkoutId));

            foreach ($checkout->items() as $item) {
                $this->reservations->release(new ReleaseInventoryCommand(
                    skuId: $item->skuId(),
                    shopId: $checkout->shopId()->value,
                    quantity: $item->quantity(),
                    sourceType: 'checkout',
                    sourceId: $command->checkoutId,
                ));
            }

            $checkout->cancel();
            $this->checkouts->save($checkout);

            $this->audit->record(
                module: 'Sales',
                eventType: 'CheckoutCancelled',
                actorStaffId: $checkout->cashierStaffId(),
                actorRoleSnapshot: null,
                subjectType: 'sales_checkout',
                subjectId: $command->checkoutId,
                beforeState: null,
                afterState: ['item_count' => count($checkout->items())],
            );
        });
    }
}
