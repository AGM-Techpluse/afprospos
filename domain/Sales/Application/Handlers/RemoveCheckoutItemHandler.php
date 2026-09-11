<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ReleaseInventoryCommand;
use Domain\Inventory\Application\Contracts\InventoryReservationService;
use Domain\Sales\Application\Commands\RemoveCheckoutItemCommand;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Sales\Domain\ValueObjects\SalesCheckoutItemId;

/**
 * Releases the item's reservation back to Available before removing it
 * from the cart (BLD §2.2). Inventory's release() targets "any N units
 * of this SKU reserved by this source," not a specific inventory_item_id
 * — correct for stock safety (Available/Reserved counts are always
 * exact) with one narrow, cosmetic edge case: if a checkout holds two
 * distinct serialized units of the *same* SKU as separate lines,
 * removing one may release a different physical unit than the one
 * displayed on that line. Not a stock-integrity issue since consume()
 * has the identical shape — acceptable for Phase 4's scope.
 */
final class RemoveCheckoutItemHandler
{
    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly InventoryReservationService $reservations,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RemoveCheckoutItemCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $checkout = $this->checkouts->lockForUpdate(new CheckoutId($command->checkoutId));

            $item = $checkout->removeItem(new SalesCheckoutItemId($command->checkoutItemId));

            $this->reservations->release(new ReleaseInventoryCommand(
                skuId: $item->skuId(),
                shopId: $checkout->shopId()->value,
                quantity: $item->quantity(),
                sourceType: 'checkout',
                sourceId: $command->checkoutId,
            ));

            $this->checkouts->save($checkout);

            $this->audit->record(
                module: 'Sales',
                eventType: 'CheckoutItemRemoved',
                actorStaffId: $checkout->cashierStaffId(),
                actorRoleSnapshot: null,
                subjectType: 'sales_checkout',
                subjectId: $command->checkoutId,
                beforeState: null,
                afterState: ['sku_id' => $item->skuId(), 'quantity' => $item->quantity()],
            );
        });
    }
}
