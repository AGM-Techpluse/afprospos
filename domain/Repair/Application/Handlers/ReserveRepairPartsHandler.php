<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ReserveInventoryCommand;
use Domain\Inventory\Application\Contracts\InventoryReservationService;
use Domain\Repair\Application\Commands\ReserveRepairPartsCommand;
use Domain\Repair\Domain\Entities\RepairPartReservation;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\Repositories\RepairPartReservationRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Implementation Plan Phase 6 exit criterion: parts are never reserved before repairability/authorization is settled — RepairJob::assertPartsReservable() is the hard gate. */
final class ReserveRepairPartsHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly RepairPartReservationRepository $reservations,
        private readonly InventoryReservationService $inventory,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ReserveRepairPartsCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $job->assertPartsReservable();

            $result = $this->inventory->reserve(new ReserveInventoryCommand(
                skuId: $command->skuId,
                shopId: $job->shopId()->value,
                quantity: $command->quantity,
                sourceType: 'repair',
                sourceId: $jobId->value,
            ));

            $inventoryItemId = $result->inventoryItemIds[0] ?? null;

            $reservation = RepairPartReservation::reserve($jobId, $inventoryItemId, $command->skuId, $command->quantity);
            $id = $this->reservations->save($reservation);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairPartsReserved',
                actorStaffId: new StaffId($command->reservedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: null,
                afterState: [
                    'repair_part_reservation_id' => $id->value,
                    'sku_id' => $command->skuId,
                    'quantity' => $command->quantity,
                    'inventory_item_id' => $inventoryItemId,
                ],
            );
        });
    }
}
