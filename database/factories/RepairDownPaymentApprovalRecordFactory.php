<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairDownPaymentApprovalRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RepairDownPaymentApprovalRecord> */
final class RepairDownPaymentApprovalRecordFactory extends Factory
{
    protected $model = RepairDownPaymentApprovalRecord::class;

    public function definition(): array
    {
        return [
            'repair_job_id' => RepairJobRecord::factory(),
            'amount_minor' => 200000,
            'method' => 'cash',
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
