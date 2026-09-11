<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Contracts;

use Domain\Inventory\Application\Commands\ConsumeInventoryCommand;
use Domain\Inventory\Application\Commands\ReleaseInventoryCommand;
use Domain\Inventory\Application\Commands\ReserveInventoryCommand;
use Domain\Inventory\Application\DTOs\ReservationResult;

/**
 * The published cross-module contract (Implementation Plan Phase 3):
 * Sales and Repair depend on this, never on Inventory's Domain/
 * internals or Eloquent models directly (CPNC §4.2). Reuses the
 * Command classes as parameter types rather than a parallel DTO
 * hierarchy — they are already framework-free readonly data carriers.
 */
interface InventoryReservationService
{
    public function reserve(ReserveInventoryCommand $command): ReservationResult;

    public function consume(ConsumeInventoryCommand $command): void;

    public function release(ReleaseInventoryCommand $command): void;

    public function isAvailable(int $skuId, int $shopId, int $quantity): bool;
}
