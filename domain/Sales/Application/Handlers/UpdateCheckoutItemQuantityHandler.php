<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ReleaseInventoryCommand;
use Domain\Inventory\Application\Commands\ReserveInventoryCommand;
use Domain\Inventory\Application\Contracts\InventoryReservationService;
use Domain\Sales\Application\Commands\UpdateCheckoutItemQuantityCommand;
use Domain\Sales\Domain\Entities\SalesCheckoutItem;
use Domain\Sales\Domain\Exceptions\SerializedItemQuantityMustBeOne;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Sales\Domain\ValueObjects\SalesCheckoutItemId;
use InvalidArgumentException;

/**
 * Only a non-serialized line's quantity can change — a serialized line
 * always reserves exactly one specific physical unit
 * (SerializedItemQuantityMustBeOne), so "more of this" means adding a
 * second line via AddCheckoutItemHandler, not growing this one. The
 * quantity delta (not the absolute value) is reserved/released against
 * Inventory so the Available/Reserved counters stay exact either way.
 */
final class UpdateCheckoutItemQuantityHandler
{
    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly InventoryReservationService $reservations,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateCheckoutItemQuantityCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $checkout = $this->checkouts->lockForUpdate(new CheckoutId($command->checkoutId));
            $itemId = new SalesCheckoutItemId($command->checkoutItemId);

            $current = $this->findItem($checkout->items(), $itemId);

            if ($current->inventoryItemId() !== null) {
                throw SerializedItemQuantityMustBeOne::forSku($current->skuId());
            }

            $delta = $command->quantity - $current->quantity();

            if ($delta > 0) {
                $this->reservations->reserve(new ReserveInventoryCommand(
                    skuId: $current->skuId(),
                    shopId: $checkout->shopId()->value,
                    quantity: $delta,
                    sourceType: 'checkout',
                    sourceId: $command->checkoutId,
                ));
            } elseif ($delta < 0) {
                $this->reservations->release(new ReleaseInventoryCommand(
                    skuId: $current->skuId(),
                    shopId: $checkout->shopId()->value,
                    quantity: -$delta,
                    sourceType: 'checkout',
                    sourceId: $command->checkoutId,
                ));
            }

            $checkout->changeItemQuantity($itemId, $command->quantity);
            $this->checkouts->save($checkout);

            $this->audit->record(
                module: 'Sales',
                eventType: 'CheckoutItemQuantityChanged',
                actorStaffId: $checkout->cashierStaffId(),
                actorRoleSnapshot: null,
                subjectType: 'sales_checkout',
                subjectId: $command->checkoutId,
                beforeState: ['quantity' => $current->quantity()],
                afterState: ['sku_id' => $current->skuId(), 'quantity' => $command->quantity],
            );
        });
    }

    /** @param  SalesCheckoutItem[]  $items */
    private function findItem(array $items, SalesCheckoutItemId $itemId): SalesCheckoutItem
    {
        foreach ($items as $item) {
            if ($item->id()?->equals($itemId)) {
                return $item;
            }
        }

        throw new InvalidArgumentException("Checkout item [{$itemId->value}] is not on this checkout.");
    }
}
