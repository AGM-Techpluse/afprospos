<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutAdjustmentRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesCheckoutAdjustmentRecord> */
final class SalesCheckoutAdjustmentRecordFactory extends Factory
{
    protected $model = SalesCheckoutAdjustmentRecord::class;

    public function definition(): array
    {
        return [
            'sales_checkout_id' => SalesCheckoutRecord::factory(),
            'type' => 'promotion',
            'source_id' => 1,
            'amount_minor' => 1000,
            'applied_order' => 1,
        ];
    }
}
