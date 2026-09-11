<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Persistence\Repositories;

use Domain\Inventory\Domain\Entities\InventoryItem;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\ValueObjects\InventoryItemId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Inventory\Infrastructure\Locking\OrderedRowLocker;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Shared\Domain\ValueObjects\ShopId;

final class EloquentInventoryItemRepository implements InventoryItemRepository
{
    public function get(InventoryItemId $id): InventoryItem
    {
        return $this->toDomain(InventoryItemRecord::query()->findOrFail($id->value));
    }

    public function findByImei(string $imei): ?InventoryItem
    {
        $record = InventoryItemRecord::query()->where('imei', $imei)->first();

        return $record !== null ? $this->toDomain($record) : null;
    }

    public function existsWithImei(string $imei): bool
    {
        return InventoryItemRecord::query()->where('imei', $imei)->exists();
    }

    public function create(SkuId $skuId, string $imei, ShopId $shopId, string $condition): InventoryItemId
    {
        $record = InventoryItemRecord::query()->create([
            'sku_id' => $skuId->value,
            'imei' => $imei,
            'current_shop_id' => $shopId->value,
            'condition' => $condition,
            'status' => 'available',
            'version' => 0,
        ]);

        return new InventoryItemId($record->id);
    }

    public function save(InventoryItem $item): void
    {
        InventoryItemRecord::query()->whereKey($item->id()->value)->update([
            'current_shop_id' => $item->currentShopId()->value,
            'status' => $item->status(),
            'reserved_by_type' => $item->reservedByType(),
            'reserved_by_id' => $item->reservedById(),
            'version' => $item->version(),
        ]);
    }

    public function findAvailableIds(SkuId $skuId, ShopId $shopId, int $limit): array
    {
        return InventoryItemRecord::query()
            ->where('sku_id', $skuId->value)
            ->where('current_shop_id', $shopId->value)
            ->where('status', 'available')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->all();
    }

    public function lockByIds(array $ids): array
    {
        return OrderedRowLocker::lock(InventoryItemRecord::query(), $ids)
            ->map(fn (InventoryItemRecord $record): InventoryItem => $this->toDomain($record))
            ->all();
    }

    public function findReservedBySource(SkuId $skuId, string $sourceType, int $sourceId, int $limit): array
    {
        return InventoryItemRecord::query()
            ->where('sku_id', $skuId->value)
            ->where('reserved_by_type', $sourceType)
            ->where('reserved_by_id', $sourceId)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->all();
    }

    private function toDomain(InventoryItemRecord $record): InventoryItem
    {
        return InventoryItem::reconstitute(
            new InventoryItemId($record->id),
            new SkuId($record->sku_id),
            $record->imei,
            new ShopId($record->current_shop_id),
            $record->condition,
            $record->status,
            $record->reserved_by_type,
            $record->reserved_by_id,
            $record->version,
        );
    }
}
