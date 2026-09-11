<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\InitiateInventoryTransferCommand;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\Repositories\InventoryTransferRepository;
use Domain\Inventory\Domain\ValueObjects\InventoryItemId;
use Domain\Inventory\Domain\ValueObjects\InventoryTransferId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * INV-BR-11/§14/ADD §15: verify transferable, change location/status,
 * write the movement record — one atomic transaction (BLD §9.2).
 */
final class InitiateInventoryTransferHandler
{
    public function __construct(
        private readonly InventoryItemRepository $items,
        private readonly InventoryStockLevelRepository $stockLevels,
        private readonly InventoryTransferRepository $transfers,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(InitiateInventoryTransferCommand $command): InventoryTransferId
    {
        return $this->atomic->run(function () use ($command): InventoryTransferId {
            $skuId = new SkuId($command->skuId);
            $fromShop = new ShopId($command->fromShopId);
            $toShop = new ShopId($command->toShopId);

            if ($command->inventoryItemId !== null) {
                $item = $this->items->get(new InventoryItemId($command->inventoryItemId));
                $item->beginTransfer();
                $this->items->save($item);
            } else {
                $level = $this->stockLevels->lockForUpdate($skuId, $fromShop);
                $level->transferOut($command->quantity ?? 0);
                $this->stockLevels->save($level);
            }

            $transferId = $this->transfers->create(
                skuId: $skuId,
                inventoryItemId: $command->inventoryItemId,
                quantity: $command->quantity,
                fromShopId: $fromShop,
                toShopId: $toShop,
                initiatedByStaffId: new StaffId($command->initiatedByStaffId),
            );

            $this->audit->record(
                module: 'Inventory',
                eventType: 'InventoryTransferInitiated',
                actorStaffId: new StaffId($command->initiatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'inventory_transfer',
                subjectId: $transferId->value,
                beforeState: null,
                afterState: [
                    'sku_id' => $skuId->value,
                    'from_shop_id' => $fromShop->value,
                    'to_shop_id' => $toShop->value,
                    'quantity' => $command->quantity,
                    'inventory_item_id' => $command->inventoryItemId,
                ],
            );

            return $transferId;
        });
    }
}
