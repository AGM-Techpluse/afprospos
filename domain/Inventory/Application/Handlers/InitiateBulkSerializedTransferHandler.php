<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Inventory\Application\Commands\InitiateBulkSerializedTransferCommand;
use Domain\Inventory\Application\Commands\InitiateInventoryTransferCommand;
use Domain\Inventory\Domain\ValueObjects\InventoryTransferId;

/**
 * Several specific serialized units (IMEIs) transferred in one submission —
 * deliberately no new multi-unit domain concept or schema (the
 * `inventory_transfers` table stays strictly one-row-per-unit, per
 * CPNC's "reuse before invent"): this just loops
 * InitiateInventoryTransferHandler's existing single-unit logic inside one
 * outer transaction, so if any unit fails (e.g. claimed by another
 * transfer between page load and submit) the whole batch rolls back
 * together via nested-transaction savepoints, rather than leaving some
 * units transferred and others not.
 */
final class InitiateBulkSerializedTransferHandler
{
    public function __construct(
        private readonly InitiateInventoryTransferHandler $initiateTransfer,
        private readonly Atomic $atomic,
    ) {}

    /** @return InventoryTransferId[] */
    public function handle(InitiateBulkSerializedTransferCommand $command): array
    {
        return $this->atomic->run(function () use ($command): array {
            $transferIds = [];

            foreach ($command->units as $unit) {
                $transferIds[] = $this->initiateTransfer->handle(new InitiateInventoryTransferCommand(
                    skuId: $unit['sku_id'],
                    inventoryItemId: $unit['inventory_item_id'],
                    quantity: null,
                    fromShopId: $command->fromShopId,
                    toShopId: $command->toShopId,
                    initiatedByStaffId: $command->initiatedByStaffId,
                ));
            }

            return $transferIds;
        });
    }
}
