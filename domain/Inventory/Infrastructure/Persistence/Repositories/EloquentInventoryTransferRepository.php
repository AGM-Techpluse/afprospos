<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Persistence\Repositories;

use Domain\Inventory\Domain\Entities\InventoryTransfer;
use Domain\Inventory\Domain\Repositories\InventoryTransferRepository;
use Domain\Inventory\Domain\ValueObjects\InventoryTransferId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryTransferRecord;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class EloquentInventoryTransferRepository implements InventoryTransferRepository
{
    public function get(InventoryTransferId $id): InventoryTransfer
    {
        return $this->toDomain(InventoryTransferRecord::query()->findOrFail($id->value));
    }

    public function create(
        SkuId $skuId,
        ?int $inventoryItemId,
        ?int $quantity,
        ShopId $fromShopId,
        ShopId $toShopId,
        StaffId $initiatedByStaffId,
    ): InventoryTransferId {
        $record = InventoryTransferRecord::query()->create([
            'sku_id' => $skuId->value,
            'inventory_item_id' => $inventoryItemId,
            'quantity' => $quantity,
            'from_shop_id' => $fromShopId->value,
            'to_shop_id' => $toShopId->value,
            'initiated_by_staff_id' => $initiatedByStaffId->value,
            'status' => 'in_transit',
        ]);

        return new InventoryTransferId($record->id);
    }

    public function save(InventoryTransfer $transfer): void
    {
        InventoryTransferRecord::query()->whereKey($transfer->id()->value)->update([
            'status' => $transfer->status(),
            'received_by_staff_id' => $transfer->receivedByStaffId()?->value,
        ]);
    }

    private function toDomain(InventoryTransferRecord $record): InventoryTransfer
    {
        return InventoryTransfer::reconstitute(
            new InventoryTransferId($record->id),
            new SkuId($record->sku_id),
            $record->inventory_item_id,
            $record->quantity,
            new ShopId($record->from_shop_id),
            new ShopId($record->to_shop_id),
            new StaffId($record->initiated_by_staff_id),
            $record->received_by_staff_id !== null ? new StaffId($record->received_by_staff_id) : null,
            $record->status,
        );
    }
}
