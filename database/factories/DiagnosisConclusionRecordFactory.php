<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisConclusionRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DiagnosisConclusionRecord> */
final class DiagnosisConclusionRecordFactory extends Factory
{
    protected $model = DiagnosisConclusionRecord::class;

    public function definition(): array
    {
        return [
            'repair_job_id' => RepairJobRecord::factory(),
            'root_cause' => 'Faulty battery causing intermittent shutdowns',
            'outcome' => 'repairable',
            'diagnosed_by_staff_id' => StaffRecord::factory(),
        ];
    }
}
