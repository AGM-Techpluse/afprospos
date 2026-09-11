<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesCheckoutRecord> */
final class SalesCheckoutRecordFactory extends Factory
{
    protected $model = SalesCheckoutRecord::class;

    public function definition(): array
    {
        return [
            'shop_id' => ShopRecord::factory(),
            'customer_id' => null,
            'cashier_staff_id' => StaffRecord::factory(),
            'status' => 'open',
            'reservation_expires_at' => now()->addMinutes(15),
            'subtotal_minor' => 0,
            'discount_minor' => 0,
            'total_minor' => 0,
            'payment_transaction_id' => null,
        ];
    }

    public function expired(): self
    {
        return $this->state(fn (): array => ['reservation_expires_at' => now()->subMinute()]);
    }
}
