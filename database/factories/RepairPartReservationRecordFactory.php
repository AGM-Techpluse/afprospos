<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairPartReservationRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RepairPartReservationRecord> */
final class RepairPartReservationRecordFactory extends Factory
{
    protected $model = RepairPartReservationRecord::class;

    public function definition(): array
    {
        return [
            'repair_job_id' => RepairJobRecord::factory(),
            'inventory_item_id' => null,
            'sku_id' => SkuRecord::factory(),
            'quantity' => 1,
            'status' => 'reserved',
        ];
    }
}
