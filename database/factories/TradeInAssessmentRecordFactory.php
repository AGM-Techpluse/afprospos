<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\TradeInAssessmentRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TradeInAssessmentRecord> */
final class TradeInAssessmentRecordFactory extends Factory
{
    protected $model = TradeInAssessmentRecord::class;

    public function definition(): array
    {
        return [
            'customer_id' => CustomerRecord::factory(),
            'related_checkout_id' => null,
            'device_description' => [
                'make' => 'Tecno',
                'model' => 'Camon 20',
                'imei' => (string) $this->faker->numerify('###############'),
                'condition' => 'good',
            ],
            'assessed_value_minor' => null,
            'resolution_state' => 'submitted',
            'assessed_by_staff_id' => null,
            'approved_by_staff_id' => null,
        ];
    }

    public function assessed(): self
    {
        return $this->state(fn (): array => [
            'resolution_state' => 'assessed',
            'assessed_value_minor' => 35000000,
            'assessed_by_staff_id' => StaffRecord::factory(),
        ]);
    }
}
