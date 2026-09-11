<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Services;

use Domain\Inventory\Domain\Entities\InventoryItem;
use Domain\Inventory\Domain\Exceptions\InsufficientAvailableStock;
use Domain\Shared\Domain\ValueObjects\ShopId;

/**
 * Given already-locked candidate units (locked in id order by the
 * Repository, per ADD §30.1 — this Service never touches the database
 * itself), decides which specific unit(s) satisfy a serialized
 * reservation request. All-or-nothing: if fewer than requested are
 * genuinely available, none are reserved (the caller rolls back).
 */
final class ReservationService
{
    /**
     * @param  InventoryItem[]  $lockedCandidates  already locked, sorted by id
     * @return InventoryItem[] the units reserved (same length as $quantity)
     */
    public function reserveSerializedUnits(
        array $lockedCandidates,
        int $quantity,
        ShopId $shop,
        string $sourceType,
        int $sourceId,
        int $skuIdForError,
    ): array {
        $available = array_values(array_filter($lockedCandidates, static fn (InventoryItem $item): bool => $item->isAvailable()));

        if (count($available) < $quantity) {
            throw InsufficientAvailableStock::forSku($skuIdForError, $shop->value, $quantity, count($available));
        }

        $toReserve = array_slice($available, 0, $quantity);

        foreach ($toReserve as $item) {
            $item->reserve($shop, $sourceType, $sourceId);
        }

        return $toReserve;
    }
}
