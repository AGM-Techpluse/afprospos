<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Queries;

use Domain\Repair\Application\Contracts\RepairJobLookup;
use Domain\Sales\Application\Contracts\SaleLookup;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyClaimRecord;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyPolicyRecord;

/** Backs Admin and Customer Claim Show pages — enriches the claim with its policy and origin summary via other modules' published Contracts (mirrors RepairJobDetailQuery composing CollectionCaseLookup/CustomerDirectoryQuery), never their raw Eloquent models. */
final class WarrantyClaimDetailQuery
{
    public function __construct(
        private readonly SaleLookup $saleLookup,
        private readonly RepairJobLookup $repairJobLookup,
    ) {}

    /** @return array<string, mixed>|null */
    public function find(int $warrantyClaimId): ?array
    {
        $claim = WarrantyClaimRecord::query()->with('customer')->find($warrantyClaimId);

        if ($claim === null) {
            return null;
        }

        $policy = WarrantyPolicyRecord::query()->find($claim->warranty_policy_id);

        return [
            'id' => $claim->id,
            'warranty_policy' => $policy === null ? null : [
                'id' => $policy->id,
                'name' => $policy->name,
                'coverage_duration_days' => $policy->coverage_duration_days,
                'coverage_start_point' => $policy->coverage_start_point,
                'covered_scope' => $policy->covered_scope,
                'exclusions' => $policy->exclusions,
                'coverage_extent' => $policy->coverage_extent,
            ],
            'originating_sale' => $claim->originating_sale_id !== null ? $this->saleLookup->find($claim->originating_sale_id) : null,
            'originating_repair_job' => $claim->originating_repair_job_id !== null ? $this->repairJobLookup->find($claim->originating_repair_job_id) : null,
            'customer_id' => $claim->customer_id,
            'customer' => $claim->customer === null ? null : [
                'name' => $claim->customer->name,
                'phone' => $claim->customer->phone,
            ],
            'inventory_item_id' => $claim->inventory_item_id,
            'resolution_state' => $claim->resolution_state,
            'assessment_notes' => $claim->assessment_notes,
            'selected_remedy' => $claim->selected_remedy,
            'remedy_reference_id' => $claim->remedy_reference_id,
            'created_at' => $claim->created_at->toIso8601String(),
        ];
    }
}
