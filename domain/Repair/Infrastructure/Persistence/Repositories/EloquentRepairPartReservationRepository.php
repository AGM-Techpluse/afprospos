<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\RepairPartReservation;
use Domain\Repair\Domain\Repositories\RepairPartReservationRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairPartReservationId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairPartReservationRecord;

final class EloquentRepairPartReservationRepository implements RepairPartReservationRepository
{
    public function findByRepairJob(RepairJobId $repairJobId): array
    {
        return RepairPartReservationRecord::query()
            ->where('repair_job_id', $repairJobId->value)
            ->get()
            ->map(fn (RepairPartReservationRecord $record): RepairPartReservation => $this->toDomain($record))
            ->all();
    }

    public function lockForUpdate(RepairPartReservationId $id): RepairPartReservation
    {
        $record = RepairPartReservationRecord::query()->whereKey($id->value)->lockForUpdate()->firstOrFail();

        return $this->toDomain($record);
    }

    public function save(RepairPartReservation $reservation): RepairPartReservationId
    {
        $attributes = [
            'repair_job_id' => $reservation->repairJobId()->value,
            'inventory_item_id' => $reservation->inventoryItemId(),
            'sku_id' => $reservation->skuId(),
            'quantity' => $reservation->quantity(),
            'status' => $reservation->status(),
        ];

        if ($reservation->id() === null) {
            $record = RepairPartReservationRecord::query()->create($attributes);
        } else {
            $record = RepairPartReservationRecord::query()->findOrFail($reservation->id()->value);
            $record->update($attributes);
        }

        return new RepairPartReservationId($record->id);
    }

    private function toDomain(RepairPartReservationRecord $record): RepairPartReservation
    {
        return RepairPartReservation::reconstitute(
            new RepairPartReservationId($record->id),
            new RepairJobId($record->repair_job_id),
            $record->inventory_item_id,
            $record->sku_id,
            $record->quantity,
            $record->status,
        );
    }
}
