<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Laravel;

use Domain\Inventory\Application\Commands\ConsumeInventoryCommand;
use Domain\Inventory\Application\Commands\ReleaseInventoryCommand;
use Domain\Inventory\Application\Commands\ReserveInventoryCommand;
use Domain\Inventory\Application\Contracts\InventoryReservationService;
use Domain\Inventory\Application\DTOs\ReservationResult;
use Domain\Inventory\Application\Handlers\ConsumeInventoryHandler;
use Domain\Inventory\Application\Handlers\ReleaseInventoryHandler;
use Domain\Inventory\Application\Handlers\ReserveInventoryHandler;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\Repositories\SkuRepository;
use Domain\Inventory\Domain\Services\StockAvailability;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;

/**
 * The Contract's default implementation — delegates straight to the
 * three write-side Handlers, so Sales/Repair depending on the Contract
 * exercise exactly the same code path as anything calling the
 * Handlers directly within Inventory itself (CPNC §4.4).
 */
final class HandlerBackedInventoryReservationService implements InventoryReservationService
{
    public function __construct(
        private readonly ReserveInventoryHandler $reserveHandler,
        private readonly ConsumeInventoryHandler $consumeHandler,
        private readonly ReleaseInventoryHandler $releaseHandler,
        private readonly SkuRepository $skus,
        private readonly InventoryStockLevelRepository $stockLevels,
        private readonly InventoryItemRepository $items,
    ) {}

    public function reserve(ReserveInventoryCommand $command): ReservationResult
    {
        return $this->reserveHandler->handle($command);
    }

    public function consume(ConsumeInventoryCommand $command): void
    {
        $this->consumeHandler->handle($command);
    }

    public function release(ReleaseInventoryCommand $command): void
    {
        $this->releaseHandler->handle($command);
    }

    /** Advisory/cheap check only — the authoritative check happens inside reserve()'s locked transaction (same split as ShopScopePolicy vs. ActorContext::canAccessShop()). */
    public function isAvailable(int $skuId, int $shopId, int $quantity): bool
    {
        $skuIdVo = new SkuId($skuId);
        $shopIdVo = new ShopId($shopId);
        $sku = $this->skus->get($skuIdVo);

        if ($sku->isSerialized()) {
            return count($this->items->findAvailableIds($skuIdVo, $shopIdVo, $quantity)) >= $quantity;
        }

        $level = $this->stockLevels->find($skuIdVo, $shopIdVo);

        return StockAvailability::available($level?->onHand() ?? 0, $level?->reserved() ?? 0) >= $quantity;
    }
}
