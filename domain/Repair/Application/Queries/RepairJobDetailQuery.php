<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Queries;

use Domain\Collection\Application\Contracts\CollectionCaseLookup;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairDiagnosisRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairPartReservationRecord;

/** Full detail: job + diagnoses + part reservations + linked collection case (via Collection's published CollectionCaseLookup contract, never its Eloquent directly — CPNC §4.2). */
final class RepairJobDetailQuery
{
    public function __construct(private readonly CollectionCaseLookup $collectionCases) {}

    /** @return array<string, mixed>|null */
    public function find(int $repairJobId): ?array
    {
        $job = RepairJobRecord::query()->find($repairJobId);

        if ($job === null) {
            return null;
        }

        $diagnoses = RepairDiagnosisRecord::query()
            ->where('repair_job_id', $repairJobId)
            ->orderBy('created_at')
            ->get()
            ->map(static fn (RepairDiagnosisRecord $diagnosis): array => [
                'id' => $diagnosis->id,
                'component' => $diagnosis->component,
                'condition' => $diagnosis->condition,
                'notes' => $diagnosis->notes,
                'outcome' => $diagnosis->outcome,
                'diagnosed_by_staff_id' => $diagnosis->diagnosed_by_staff_id,
                'created_at' => $diagnosis->created_at->toIso8601String(),
            ])->all();

        $partReservations = RepairPartReservationRecord::query()
            ->where('repair_job_id', $repairJobId)
            ->get()
            ->map(static fn (RepairPartReservationRecord $reservation): array => [
                'id' => $reservation->id,
                'inventory_item_id' => $reservation->inventory_item_id,
                'sku_id' => $reservation->sku_id,
                'quantity' => $reservation->quantity,
                'status' => $reservation->status,
            ])->all();

        $collectionCase = $this->collectionCases->findLatestBySource('repair_job', $repairJobId);

        return [
            'id' => $job->id,
            'shop_id' => $job->shop_id,
            'customer_id' => $job->customer_id,
            'device_make' => $job->device_make,
            'device_model' => $job->device_model,
            'technician_staff_id' => $job->technician_staff_id,
            'labour_charge_minor' => $job->labour_charge_minor,
            'down_payment_required_minor' => $job->down_payment_required_minor,
            'down_payment_deadline_at' => $job->down_payment_deadline_at?->toIso8601String(),
            'repair_status' => $job->repair_status,
            'financial_status' => $job->financial_status,
            'estimated_collection_date' => $job->estimated_collection_date?->toDateString(),
            'unrepairable_settlement_state' => $job->unrepairable_settlement_state,
            'created_at' => $job->created_at->toIso8601String(),
            'diagnoses' => $diagnoses,
            'part_reservations' => $partReservations,
            'collection_case' => $collectionCase,
        ];
    }
}
