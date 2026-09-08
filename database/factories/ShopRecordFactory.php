<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShopRecord> */
final class ShopRecordFactory extends Factory
{
    protected $model = ShopRecord::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'sku_prefix_code' => strtoupper(fake()->unique()->lexify('???')),
            'address' => fake()->address(),
            'contact_phone' => fake()->phoneNumber(),
            'contact_email' => fake()->companyEmail(),
            'offline_policy' => ['cash_sales' => true, 'bank_transfer_confirmation' => false],
            'status' => 'active',
        ];
    }
}
