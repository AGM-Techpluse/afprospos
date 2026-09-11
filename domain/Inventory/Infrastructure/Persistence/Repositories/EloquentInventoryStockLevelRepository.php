<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Persistence\Repositories;

use Domain\Inventory\Domain\Entities\InventoryStockLevel;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Inventory\Domain\ValueObjects\StockLevelId;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Shared\Domain\ValueObjects\ShopId;

final class EloquentInventoryStockLevelRepository implements InventoryStockLevelRepository
{
    public function find(SkuId $skuId, ShopId $shopId): ?InventoryStockLevel
    {
        $record = InventoryStockLevelRecord::query()
            ->where('sku_id', $skuId->value)
            ->where('shop_id', $shopId->value)
            ->first();

        return $record !== null ? $this->toDomain($record) : null;
    }

    public function lockForUpdate(SkuId $skuId, ShopId $shopId): InventoryStockLevel
    {
        // Row must exist before it can be locked — create it (0/0) first
        // if this is the sku/shop pair's very first stock movement.
        InventoryStockLevelRecord::query()->firstOrCreate(
            ['sku_id' => $skuId->value, 'shop_id' => $shopId->value],
            ['on_hand' => 0, 'reserved' => 0, 'version' => 0],
        );

        $record = InventoryStockLevelRecord::query()
            ->where('sku_id', $skuId->value)
            ->where('shop_id', $shopId->value)
            ->lockForUpdate()
            ->firstOrFail();

        return $this->toDomain($record);
    }

    public function save(InventoryStockLevel $level): void
    {
        InventoryStockLevelRecord::query()->whereKey($level->id()->value)->update([
            'on_hand' => $level->onHand(),
            'reserved' => $level->reserved(),
            'version' => $level->version(),
        ]);
    }

    private function toDomain(InventoryStockLevelRecord $record): InventoryStockLevel
    {
        return InventoryStockLevel::reconstitute(
            new StockLevelId($record->id),
            new SkuId($record->sku_id),
            new ShopId($record->shop_id),
            $record->on_hand,
            $record->reserved,
            $record->version,
        );
    }
}
