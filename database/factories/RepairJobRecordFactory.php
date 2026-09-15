<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RepairJobRecord> */
final class RepairJobRecordFactory extends Factory
{
    protected $model = RepairJobRecord::class;

    public function definition(): array
    {
        return [
            'shop_id' => ShopRecord::factory(),
            'customer_id' => CustomerRecord::factory(),
            'device_make' => fake()->randomElement(['Apple', 'Samsung', 'Tecno', 'Infinix']),
            'device_model' => fake()->bothify('Model-###'),
            'technician_staff_id' => null,
            'labour_charge_minor' => 500000,
            'down_payment_required_minor' => null,
            'down_payment_deadline_at' => null,
            'repair_status' => 'received',
            'financial_status' => 'unpaid',
            'estimated_collection_date' => null,
            'unrepairable_settlement_state' => null,
        ];
    }
}
