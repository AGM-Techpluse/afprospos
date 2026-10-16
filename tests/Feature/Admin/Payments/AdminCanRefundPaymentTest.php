<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Payments;

use Database\Seeders\RolePermissionSeeder;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanRefundPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_can_fully_refund_a_confirmed_payment_with_no_amount_given(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $transaction = PaymentTransactionRecord::factory()->create(['status' => 'confirmed', 'amount_minor' => 5000]);

        $response = $this->actingAs($accountant, 'staff')->post("/admin/payments/{$transaction->id}/refund");

        $response->assertRedirect("/admin/payments/{$transaction->id}");
        $this->assertDatabaseHas('payments_transactions', ['id' => $transaction->id, 'status' => 'refunded']);
        $this->assertDatabaseHas('payment_refunds', [
            'payment_transaction_id' => $transaction->id,
            'amount_minor' => 5000,
            'refunded_by_staff_id' => $accountant->id,
            'source' => 'manual_refund',
        ]);
    }

    public function test_accountant_can_partially_refund_a_confirmed_payment_and_refund_the_remainder_later(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $transaction = PaymentTransactionRecord::factory()->create(['status' => 'confirmed', 'amount_minor' => 5000]);

        $firstResponse = $this->actingAs($accountant, 'staff')->post("/admin/payments/{$transaction->id}/refund", [
            'amount_minor' => 2000,
        ]);

        $firstResponse->assertRedirect("/admin/payments/{$transaction->id}");
        $this->assertDatabaseHas('payments_transactions', ['id' => $transaction->id, 'status' => 'partially_refunded']);

        $secondResponse = $this->actingAs($accountant, 'staff')->post("/admin/payments/{$transaction->id}/refund", [
            'amount_minor' => 3000,
        ]);

        $secondResponse->assertRedirect("/admin/payments/{$transaction->id}");
        $this->assertDatabaseHas('payments_transactions', ['id' => $transaction->id, 'status' => 'refunded']);
        $this->assertDatabaseCount('payment_refunds', 2);
    }

    public function test_refunding_more_than_the_remaining_balance_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $transaction = PaymentTransactionRecord::factory()->create(['status' => 'confirmed', 'amount_minor' => 5000]);

        $response = $this->actingAs($accountant, 'staff')->post("/admin/payments/{$transaction->id}/refund", [
            'amount_minor' => 6000,
        ]);

        $response->assertSessionHasErrors('amount_minor');
        $this->assertDatabaseHas('payments_transactions', ['id' => $transaction->id, 'status' => 'confirmed']);
        $this->assertDatabaseCount('payment_refunds', 0);
    }
}
