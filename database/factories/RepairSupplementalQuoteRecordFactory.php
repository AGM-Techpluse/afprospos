<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairSupplementalQuoteRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RepairSupplementalQuoteRecord> */
final class RepairSupplementalQuoteRecordFactory extends Factory
{
    protected $model = RepairSupplementalQuoteRecord::class;

    public function definition(): array
    {
        return [
            'repair_job_id' => RepairJobRecord::factory(),
            'amount_minor' => 50000,
            'reason' => 'Additional parts needed beyond the original quote.',
            'requested_by_staff_id' => StaffRecord::factory(),
            'status' => 'pending',
        ];
    }

    public function approved(): self
    {
        return $this->state(fn (): array => [
            'status' => 'approved',
            'approved_by_staff_id' => StaffRecord::factory(),
            'decided_at' => now(),
        ]);
    }
}
