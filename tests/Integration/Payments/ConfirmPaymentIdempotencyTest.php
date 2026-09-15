<?php

declare(strict_types=1);

namespace Tests\Integration\Payments;

use Domain\Payments\Application\Commands\ConfirmPaymentCommand;
use Domain\Payments\Application\Handlers\ConfirmPaymentHandler;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** DBDD §16.2: a duplicate confirm call must never fail or downgrade an already-confirmed payment. */
class ConfirmPaymentIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirming_the_same_payment_twice_stays_confirmed_and_does_not_throw(): void
    {
        $staffA = StaffRecord::factory()->create();
        $staffB = StaffRecord::factory()->create();

        $transaction = PaymentTransactionRecord::factory()->pendingConfirmation()->create();

        app(ConfirmPaymentHandler::class)->handle(new ConfirmPaymentCommand(
            transactionId: $transaction->id,
            confirmedByStaffId: $staffA->id,
        ));

        // Second confirm — by a different staff member — must not throw and must not overwrite the first confirmer.
        app(ConfirmPaymentHandler::class)->handle(new ConfirmPaymentCommand(
            transactionId: $transaction->id,
            confirmedByStaffId: $staffB->id,
        ));

        $this->assertDatabaseHas('payments_transactions', [
            'id' => $transaction->id,
            'status' => 'confirmed',
            'confirmed_by_staff_id' => $staffA->id,
        ]);
    }
}
