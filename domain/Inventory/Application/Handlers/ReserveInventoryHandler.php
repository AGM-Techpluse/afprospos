<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ReserveInventoryCommand;
use Domain\Inventory\Application\DTOs\ReservationResult;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\Repositories\SkuRepository;
use Domain\Inventory\Domain\Services\ReservationService;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;

/**
 * The reservation engine (DBDD §25, ADD §15/§30). One Command, two
 * internal shapes — a single lock+check+mutate sequence for
 * non-serialized SKUs, and the select-candidates -> lock-in-id-order
 * -> filter-still-available -> reserve sequence for serialized ones.
 * `Atomic::run` (attempts: 3) provides the deadlock retry ADD §30.2
 * requires; a genuine InsufficientAvailableStock is a business
 * exception and is never retried.
 */
final class ReserveInventoryHandler
{
    private const CANDIDATE_OVERFETCH = 5;

    public function __construct(
        private readonly SkuRepository $skus,
        private readonly InventoryItemRepository $items,
        private readonly InventoryStockLevelRepository $stockLevels,
        private readonly ReservationService $reservationService,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ReserveInventoryCommand $command): ReservationResult
    {
        return $this->atomic->run(function () use ($command): ReservationResult {
            $skuId = new SkuId($command->skuId);
            $shopId = new ShopId($command->shopId);
            $sku = $this->skus->get($skuId);

            $itemIds = $sku->isSerialized()
                ? $this->reserveSerialized($command, $skuId, $shopId)
                : $this->reserveNonSerialized($command, $skuId, $shopId);

            $this->audit->record(
                module: 'Inventory',
                eventType: 'InventoryReserved',
                actorStaffId: null,
                actorRoleSnapshot: null,
                subjectType: 'sku',
                subjectId: $skuId->value,
                beforeState: null,
                afterState: [
                    'shop_id' => $shopId->value,
                    'quantity' => $command->quantity,
                    'source_type' => $command->sourceType,
                    'source_id' => $command->sourceId,
                    'inventory_item_ids' => $itemIds,
                ],
            );

            return new ReservationResult($skuId->value, $shopId->value, $command->quantity, $itemIds);
        });
    }

    /** @return int[] */
    private function reserveSerialized(ReserveInventoryCommand $command, SkuId $skuId, ShopId $shopId): array
    {
        $candidateIds = $this->items->findAvailableIds($skuId, $shopId, $command->quantity + self::CANDIDATE_OVERFETCH);
        $lockedItems = $this->items->lockByIds($candidateIds);

        $reserved = $this->reservationService->reserveSerializedUnits(
            $lockedItems,
            $command->quantity,
            $shopId,
            $command->sourceType,
            $command->sourceId,
            $skuId->value,
        );

        foreach ($reserved as $item) {
            $this->items->save($item);
        }

        return array_map(static fn ($item) => $item->id()->value, $reserved);
    }

    /** @return int[] */
    private function reserveNonSerialized(ReserveInventoryCommand $command, SkuId $skuId, ShopId $shopId): array
    {
        $level = $this->stockLevels->lockForUpdate($skuId, $shopId);
        $level->reserve($command->quantity);
        $this->stockLevels->save($level);

        return [];
    }
}
