<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\ReturnRequestRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReturnRequestRecord> */
final class ReturnRequestRecordFactory extends Factory
{
    protected $model = ReturnRequestRecord::class;

    public function definition(): array
    {
        return [
            'sale_id' => $this->faker->numberBetween(1, 1000),
            'customer_id' => CustomerRecord::factory(),
            'resolution_state' => 'requested',
            'return_window_expires_at' => now()->addDays(14),
            'denial_reason' => null,
            'override_approved_by_staff_id' => null,
            'refund_transaction_id' => null,
        ];
    }

    public function expired(): self
    {
        return $this->state(fn (): array => ['return_window_expires_at' => now()->subDay()]);
    }

    public function denied(): self
    {
        return $this->state(fn (): array => ['resolution_state' => 'denied', 'denial_reason' => 'Outside the return window.']);
    }
}
