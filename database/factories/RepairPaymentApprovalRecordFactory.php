<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairPaymentApprovalRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RepairPaymentApprovalRecord> */
final class RepairPaymentApprovalRecordFactory extends Factory
{
    protected $model = RepairPaymentApprovalRecord::class;

    public function definition(): array
    {
        return [
            'repair_job_id' => RepairJobRecord::factory(),
            'requested_financial_status' => 'fully_paid',
            'amount_minor' => 500000,
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
