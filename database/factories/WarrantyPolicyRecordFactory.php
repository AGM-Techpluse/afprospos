<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyPolicyRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WarrantyPolicyRecord> */
final class WarrantyPolicyRecordFactory extends Factory
{
    protected $model = WarrantyPolicyRecord::class;

    public function definition(): array
    {
        return [
            'name' => 'Standard 12-month warranty',
            'coverage_duration_days' => 365,
            'coverage_start_point' => 'sale_date',
            'covered_scope' => ['Manufacturing defects', 'Internal component failure'],
            'exclusions' => ['Screen breakage', 'Liquid damage'],
            'available_remedies' => ['repair', 'replace', 'refund'],
            'coverage_extent' => 'full',
        ];
    }
}
