<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Queries;

use Domain\Collection\Application\Contracts\CollectionCaseLookup;
use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairDiagnosisRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobPhotoRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobProblemTagRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairPartReservationRecord;
use Illuminate\Support\Facades\Storage;

/** Full detail: job + diagnoses + part reservations + linked collection case (via Collection's published CollectionCaseLookup contract, never its Eloquent directly — CPNC §4.2). */
final class RepairJobDetailQuery
{
    public function __construct(
        private readonly CollectionCaseLookup $collectionCases,
        private readonly CustomerDirectoryQuery $customers,
        private readonly InventoryCatalogQuery $catalog,
    ) {}

    /**
     * `$viewingStaffId`/`$viewerIsOwner` gate `device_lock_value` — only the
     * job's assigned technician or the Shop Owner ever gets the decrypted
     * passcode/pattern back; everyone else gets `device_lock_present` so the
     * UI can show "set, hidden from you" instead of looking like there's no
     * lock at all.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $repairJobId, ?int $viewingStaffId = null, bool $viewerIsOwner = false): ?array
    {
        $job = RepairJobRecord::query()->find($repairJobId);

        if ($job === null) {
            return null;
        }

        $canViewDeviceLock = $viewerIsOwner || ($viewingStaffId !== null && $viewingStaffId === $job->technician_staff_id);

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
            ->map(function (RepairPartReservationRecord $reservation) use ($job): array {
                $sku = $this->catalog->find($reservation->sku_id, $job->shop_id);

                return [
                    'id' => $reservation->id,
                    'inventory_item_id' => $reservation->inventory_item_id,
                    'sku_id' => $reservation->sku_id,
                    'sku_code' => $sku['sku_code'] ?? null,
                    'product_name' => $sku['product_name'] ?? null,
                    'quantity' => $reservation->quantity,
                    'status' => $reservation->status,
                ];
            })->all();

        $collectionCase = $this->collectionCases->findLatestBySource('repair_job', $repairJobId);

        $photos = RepairJobPhotoRecord::query()
            ->where('repair_job_id', $repairJobId)
            ->orderByDesc('created_at')
            ->get()
            ->map(static fn (RepairJobPhotoRecord $photo): array => [
                'id' => $photo->id,
                'url' => Storage::disk('public')->url($photo->path),
                'caption' => $photo->caption,
                'created_at' => $photo->created_at->toIso8601String(),
            ])->all();

        $problemTags = RepairJobProblemTagRecord::query()
            ->where('repair_job_id', $repairJobId)
            ->get()
            ->map(static fn (RepairJobProblemTagRecord $tag): array => ['id' => $tag->id, 'label' => $tag->label_snapshot])
            ->all();

        return [
            'id' => $job->id,
            'shop_id' => $job->shop_id,
            'customer_id' => $job->customer_id,
            'customer_name' => $this->customers->find($job->customer_id)['name'] ?? null,
            'device_make' => $job->device_make,
            'device_model' => $job->device_model,
            'reported_issue' => $job->reported_issue,
            'device_imei_serial' => $job->device_imei_serial,
            'device_lock_type' => $job->device_lock_type,
            'device_lock_present' => $job->device_lock_type !== 'none',
            'device_lock_value' => $canViewDeviceLock ? $job->device_lock_value : null,
            'device_lock_visible_to_you' => $canViewDeviceLock,
            'problem_tags' => $problemTags,
            'technician_staff_id' => $job->technician_staff_id,
            'labour_charge_minor' => $job->labour_charge_minor,
            'down_payment_required_minor' => $job->down_payment_required_minor,
            'down_payment_deadline_at' => $job->down_payment_deadline_at?->toIso8601String(),
            'repair_status' => $job->repair_status,
            'financial_status' => $job->financial_status,
            'estimated_collection_date' => $job->estimated_collection_date?->toDateString(),
            'unrepairable_settlement_state' => $job->unrepairable_settlement_state,
            'resolution_notes' => $job->resolution_notes,
            'created_at' => $job->created_at->toIso8601String(),
            'diagnoses' => $diagnoses,
            'part_reservations' => $partReservations,
            'collection_case' => $collectionCase,
            'photos' => $photos,
        ];
    }
}
