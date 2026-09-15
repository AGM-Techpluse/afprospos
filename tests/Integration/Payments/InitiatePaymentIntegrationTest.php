<?php

declare(strict_types=1);

namespace Tests\Integration\Payments;

use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\Handlers\InitiatePaymentHandler;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Command -> Handler -> real repository/gateway -> real DB, no HTTP (CPNC §6's Integration tier). */
class InitiatePaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_payment_confirms_immediately(): void
    {
        $staff = StaffRecord::factory()->create();

        $result = app(InitiatePaymentHandler::class)->handle(new InitiatePaymentCommand(
            payableType: 'sales_checkout',
            payableId: 1,
            method: 'cash',
            amountMinor: 5000,
            initiatedByStaffId: $staff->id,
        ));

        $this->assertSame('confirmed', $result->status);
        $this->assertDatabaseHas('payments_transactions', [
            'id' => $result->transactionId,
            'status' => 'confirmed',
            'confirmed_by_staff_id' => $staff->id,
        ]);
    }

    public function test_pos_terminal_payment_confirms_immediately(): void
    {
        $staff = StaffRecord::factory()->create();

        $result = app(InitiatePaymentHandler::class)->handle(new InitiatePaymentCommand(
            payableType: 'sales_checkout',
            payableId: 1,
            method: 'pos_terminal',
            amountMinor: 7500,
            initiatedByStaffId: $staff->id,
            providerReference: 'TERM-REF-1',
        ));

        $this->assertSame('confirmed', $result->status);
        $this->assertDatabaseHas('payments_transactions', [
            'id' => $result->transactionId,
            'status' => 'confirmed',
            'provider_reference' => 'TERM-REF-1',
        ]);
    }

    public function test_bank_transfer_payment_stays_pending_confirmation(): void
    {
        $staff = StaffRecord::factory()->create();

        $result = app(InitiatePaymentHandler::class)->handle(new InitiatePaymentCommand(
            payableType: 'sales_checkout',
            payableId: 2,
            method: 'bank_transfer',
            amountMinor: 12000,
            initiatedByStaffId: $staff->id,
            providerReference: 'BANK-REF-1',
        ));

        $this->assertSame('payment_pending_confirmation', $result->status);
        $this->assertDatabaseHas('payments_transactions', [
            'id' => $result->transactionId,
            'status' => 'payment_pending_confirmation',
            'confirmed_by_staff_id' => null,
        ]);
    }
}
