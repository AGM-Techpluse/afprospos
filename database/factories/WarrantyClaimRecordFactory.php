<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyClaimRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WarrantyClaimRecord> */
final class WarrantyClaimRecordFactory extends Factory
{
    protected $model = WarrantyClaimRecord::class;

    public function definition(): array
    {
        return [
            'warranty_policy_id' => WarrantyPolicyRecordFactory::new(),
            'originating_sale_id' => null,
            'originating_repair_job_id' => null,
            'customer_id' => CustomerRecord::factory(),
            'inventory_item_id' => null,
            'resolution_state' => 'submitted',
            'assessment_notes' => null,
            'selected_remedy' => null,
            'remedy_reference_id' => null,
        ];
    }
}
