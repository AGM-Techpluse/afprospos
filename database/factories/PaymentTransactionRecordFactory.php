<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentTransactionRecord> */
final class PaymentTransactionRecordFactory extends Factory
{
    protected $model = PaymentTransactionRecord::class;

    public function definition(): array
    {
        return [
            'payable_type' => 'sales_checkout',
            'payable_id' => 1,
            'method' => 'cash',
            'amount_minor' => 10000,
            'status' => 'confirmed',
            'provider_reference' => null,
            'confirmed_by_staff_id' => null,
            'dispute_opened_at' => null,
            'dispute_proof_reference' => null,
            'dispute_resolved_by_staff_id' => null,
        ];
    }

    public function pendingConfirmation(): self
    {
        return $this->state(fn (): array => ['status' => 'payment_pending_confirmation', 'method' => 'bank_transfer']);
    }

    public function disputed(): self
    {
        return $this->state(fn (): array => ['status' => 'disputed', 'dispute_opened_at' => now()]);
    }
}
