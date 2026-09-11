<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductRecord> */
final class ProductRecordFactory extends Factory
{
    protected $model = ProductRecord::class;

    public function definition(): array
    {
        return [
            'brand' => fake()->randomElement(['Apple', 'Samsung', 'Tecno', 'Infinix']),
            'model' => fake()->bothify('Model ###'),
            'category' => 'Phones',
        ];
    }
}
