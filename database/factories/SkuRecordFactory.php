<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SkuRecord> */
final class SkuRecordFactory extends Factory
{
    protected $model = SkuRecord::class;

    public function definition(): array
    {
        $costPrice = fake()->numberBetween(50_000_00, 500_000_00);
        $markup = fake()->randomFloat(2, 10, 30);

        return [
            'product_id' => ProductRecord::factory(),
            'sku_code' => strtoupper(fake()->unique()->bothify('???-???-######')),
            'attributes' => ['color' => fake()->safeColorName(), 'storage' => '128GB'],
            'is_serialized' => true,
            'cost_price_minor' => $costPrice,
            'markup_percent' => $markup,
            'selling_price_minor' => (int) round($costPrice * (1 + $markup / 100)),
            'selling_price_overridden' => false,
            'low_stock_threshold' => 5,
        ];
    }

    public function nonSerialized(): self
    {
        return $this->state(fn (): array => ['is_serialized' => false]);
    }
}
