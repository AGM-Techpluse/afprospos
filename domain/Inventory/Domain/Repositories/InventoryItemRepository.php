<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Repositories;

use Domain\Inventory\Domain\Entities\InventoryItem;
use Domain\Inventory\Domain\ValueObjects\InventoryItemId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;

interface InventoryItemRepository
{
    public function get(InventoryItemId $id): InventoryItem;

    public function findByImei(string $imei): ?InventoryItem;

    public function existsWithImei(string $imei): bool;

    public function create(SkuId $skuId, string $imei, ShopId $shopId, string $condition): InventoryItemId;

    public function save(InventoryItem $item): void;

    /**
     * Unlocked candidate read — the ids to attempt locking, not a
     * guarantee any of them are still available once locked (DBDD
     * §25.2's two-phase select-then-lock-then-recheck).
     *
     * @return int[]
     */
    public function findAvailableIds(SkuId $skuId, ShopId $shopId, int $limit): array;

    /**
     * Locks the given rows with SELECT ... FOR UPDATE, always in
     * ascending id order regardless of the order $ids was passed in
     * (ADD §30.1 — "never dynamically lock inventory rows in
     * arbitrary order").
     *
     * @param  int[]  $ids
     * @return InventoryItem[]
     */
    public function lockByIds(array $ids): array;

    /**
     * Unlocked candidate read of units already marked
     * reserved_by_type/reserved_by_id for this source — used by
     * consume()/release() so the caller only needs to remember its own
     * source id, not specific item ids (the reservation marker fields
     * ARE the per-source ledger for serialized inventory).
     *
     * @return int[]
     */
    public function findReservedBySource(SkuId $skuId, string $sourceType, int $sourceId, int $limit): array;
}
