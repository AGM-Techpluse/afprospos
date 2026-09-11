<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ReserveInventoryCommand;
use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Inventory\Application\Contracts\InventoryReservationService;
use Domain\Sales\Application\Commands\AddCheckoutItemCommand;
use Domain\Sales\Domain\Entities\SalesCheckoutItem;
use Domain\Sales\Domain\Exceptions\SerializedItemQuantityMustBeOne;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Shared\Domain\ValueObjects\Money;
use RuntimeException;

/**
 * Reserves inventory for one cart line (BLD §4.1: "generating a
 * checkout reserves inventory; it does not sell it") and snapshots the
 * SKU's current selling price onto the line (DBDD §28). The
 * reservation happens first — if it fails (insufficient stock, a
 * concurrent checkout just took the last unit), nothing is added to
 * the cart.
 */
final class AddCheckoutItemHandler
{
    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly InventoryReservationService $reservations,
        private readonly InventoryCatalogQuery $catalog,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(AddCheckoutItemCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $checkout = $this->checkouts->lockForUpdate(new CheckoutId($command->checkoutId));

            $sku = $this->catalog->find($command->skuId, $checkout->shopId()->value);

            if ($sku === null) {
                throw new RuntimeException("SKU [{$command->skuId}] does not exist.");
            }

            if ($sku['is_serialized'] && $command->quantity !== 1) {
                throw SerializedItemQuantityMustBeOne::forSku($command->skuId);
            }

            $result = $this->reservations->reserve(new ReserveInventoryCommand(
                skuId: $command->skuId,
                shopId: $checkout->shopId()->value,
                quantity: $command->quantity,
                sourceType: 'checkout',
                sourceId: $command->checkoutId,
            ));

            $item = SalesCheckoutItem::create(
                $command->skuId,
                $result->inventoryItemIds[0] ?? null,
                $command->quantity,
                new Money($sku['selling_price_minor']),
            );

            $checkout->addItem($item);
            $this->checkouts->save($checkout);

            $this->audit->record(
                module: 'Sales',
                eventType: 'CheckoutItemAdded',
                actorStaffId: $checkout->cashierStaffId(),
                actorRoleSnapshot: null,
                subjectType: 'sales_checkout',
                subjectId: $command->checkoutId,
                beforeState: null,
                afterState: ['sku_id' => $command->skuId, 'quantity' => $command->quantity, 'inventory_item_ids' => $result->inventoryItemIds],
            );
        });
    }
}
