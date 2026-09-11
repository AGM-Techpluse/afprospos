<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutItemRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesCheckoutItemRecord> */
final class SalesCheckoutItemRecordFactory extends Factory
{
    protected $model = SalesCheckoutItemRecord::class;

    public function definition(): array
    {
        return [
            'sales_checkout_id' => SalesCheckoutRecord::factory(),
            'inventory_item_id' => null,
            'sku_id' => SkuRecord::factory()->nonSerialized(),
            'quantity' => 1,
            'unit_price_minor' => 10000,
            'warranty_policy_id' => null,
        ];
    }
}
