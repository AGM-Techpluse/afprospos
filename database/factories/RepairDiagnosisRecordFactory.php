<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairDiagnosisRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RepairDiagnosisRecord> */
final class RepairDiagnosisRecordFactory extends Factory
{
    protected $model = RepairDiagnosisRecord::class;

    public function definition(): array
    {
        return [
            'repair_job_id' => RepairJobRecord::factory(),
            'component' => 'screen',
            'condition' => 'faulty',
            'notes' => null,
            'outcome' => null,
            'diagnosed_by_staff_id' => StaffRecord::factory(),
        ];
    }
}
