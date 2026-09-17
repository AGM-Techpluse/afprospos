<?php

declare(strict_types=1);

namespace Domain\Warranty\Infrastructure\Persistence\Repositories;

use Domain\Warranty\Domain\Entities\WarrantyClaim;
use Domain\Warranty\Domain\Repositories\WarrantyClaimRepository;
use Domain\Warranty\Domain\ValueObjects\WarrantyClaimId;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyClaimRecord;

final class EloquentWarrantyClaimRepository implements WarrantyClaimRepository
{
    public function get(WarrantyClaimId $id): WarrantyClaim
    {
        return $this->toDomain(WarrantyClaimRecord::query()->findOrFail($id->value));
    }

    public function lockForUpdate(WarrantyClaimId $id): WarrantyClaim
    {
        $record = WarrantyClaimRecord::query()->whereKey($id->value)->lockForUpdate()->firstOrFail();

        return $this->toDomain($record);
    }

    public function save(WarrantyClaim $claim): WarrantyClaimId
    {
        $attributes = [
            'warranty_policy_id' => $claim->warrantyPolicyId(),
            'originating_sale_id' => $claim->originatingSaleId(),
            'originating_repair_job_id' => $claim->originatingRepairJobId(),
            'customer_id' => $claim->customerId(),
            'inventory_item_id' => $claim->inventoryItemId(),
            'resolution_state' => $claim->resolutionState(),
            'assessment_notes' => $claim->assessmentNotes(),
            'selected_remedy' => $claim->selectedRemedy(),
            'remedy_reference_id' => $claim->remedyReferenceId(),
        ];

        if ($claim->id() === null) {
            $record = WarrantyClaimRecord::query()->create($attributes);
        } else {
            $record = WarrantyClaimRecord::query()->findOrFail($claim->id()->value);
            $record->update($attributes);
        }

        return new WarrantyClaimId($record->id);
    }

    private function toDomain(WarrantyClaimRecord $record): WarrantyClaim
    {
        return WarrantyClaim::reconstitute(
            new WarrantyClaimId($record->id),
            $record->warranty_policy_id,
            $record->originating_sale_id,
            $record->originating_repair_job_id,
            $record->customer_id,
            $record->inventory_item_id,
            $record->resolution_state,
            $record->assessment_notes,
            $record->selected_remedy,
            $record->remedy_reference_id,
        );
    }
}
