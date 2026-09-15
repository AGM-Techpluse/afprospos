<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ConsumeInventoryCommand;
use Domain\Inventory\Application\Contracts\InventoryReservationService;
use Domain\Repair\Application\Commands\InstallRepairPartCommand;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\Repositories\RepairPartReservationRepository;
use Domain\Repair\Domain\ValueObjects\RepairPartReservationId;

/** The real Inventory-consumption point (Phase 6 scope decision: reservation is never silent consumption). */
final class InstallRepairPartHandler
{
    public function __construct(
        private readonly RepairPartReservationRepository $reservations,
        private readonly RepairJobRepository $repairJobs,
        private readonly InventoryReservationService $inventory,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(InstallRepairPartCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new RepairPartReservationId($command->repairPartReservationId);
            $reservation = $this->reservations->lockForUpdate($id);
            $job = $this->repairJobs->get($reservation->repairJobId());

            $reservation->markInstalled();

            $this->inventory->consume(new ConsumeInventoryCommand(
                skuId: $reservation->skuId(),
                shopId: $job->shopId()->value,
                quantity: $reservation->quantity(),
                sourceType: 'repair',
                sourceId: $reservation->repairJobId()->value,
            ));

            $this->reservations->save($reservation);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairPartInstalled',
                actorStaffId: null,
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $reservation->repairJobId()->value,
                beforeState: null,
                afterState: ['repair_part_reservation_id' => $id->value],
            );
        });
    }
}
