<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Services;

use Domain\Inventory\Application\Commands\ReleaseInventoryCommand;
use Domain\Inventory\Application\Contracts\InventoryReservationService;
use Domain\Repair\Domain\Repositories\RepairPartReservationRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;

/** Hands a cancelled job's still-reserved parts back to Inventory, so a cancelled repair never keeps stock locked. */
final class ReleaseRepairPartsService
{
    public function __construct(
        private readonly RepairPartReservationRepository $reservations,
        private readonly InventoryReservationService $inventory,
    ) {}

    public function releaseOutstanding(RepairJobId $repairJobId, int $shopId): void
    {
        foreach ($this->reservations->findByRepairJob($repairJobId) as $reservation) {
            if ($reservation->status() !== 'reserved') {
                continue;
            }

            $reservation->markReleased();
            $this->reservations->save($reservation);

            $this->inventory->release(new ReleaseInventoryCommand(
                skuId: $reservation->skuId(),
                shopId: $shopId,
                quantity: $reservation->quantity(),
                sourceType: 'repair',
                sourceId: $repairJobId->value,
            ));
        }
    }
}
