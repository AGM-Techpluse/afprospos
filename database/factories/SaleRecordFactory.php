<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SaleRecord> */
final class SaleRecordFactory extends Factory
{
    protected $model = SaleRecord::class;

    public function definition(): array
    {
        return [
            'sales_checkout_id' => SalesCheckoutRecord::factory()->state(['status' => 'paid']),
            'shop_id' => ShopRecord::factory(),
            'customer_id' => null,
            'cashier_staff_id' => StaffRecord::factory(),
            'total_minor' => 10000,
            'payment_transaction_id' => null,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'invoice_number' => fake()->unique()->numerify('INV-TST-######'),
        ];
    }
}
