<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Payments;

use Database\Seeders\RolePermissionSeeder;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanConfirmBankTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_can_confirm_a_pending_bank_transfer(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $transaction = PaymentTransactionRecord::factory()->pendingConfirmation()->create();

        $response = $this->actingAs($accountant, 'staff')->post("/admin/payments/{$transaction->id}/confirm", [
            'provider_reference' => 'BANK-CONFIRMED-1',
        ]);

        $response->assertRedirect("/admin/payments/{$transaction->id}");
        $this->assertDatabaseHas('payments_transactions', [
            'id' => $transaction->id,
            'status' => 'confirmed',
            'confirmed_by_staff_id' => $accountant->id,
            'provider_reference' => 'BANK-CONFIRMED-1',
        ]);
    }

    public function test_a_staff_member_without_payments_confirm_cannot_confirm_a_payment(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // no payments.* per config/afprospos.php

        $transaction = PaymentTransactionRecord::factory()->pendingConfirmation()->create();

        $response = $this->actingAs($cashier, 'staff')->post("/admin/payments/{$transaction->id}/confirm");

        $response->assertForbidden();
        $this->assertDatabaseHas('payments_transactions', ['id' => $transaction->id, 'status' => 'payment_pending_confirmation']);
    }

    public function test_accountant_can_reject_a_pending_bank_transfer(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $transaction = PaymentTransactionRecord::factory()->pendingConfirmation()->create();

        $response = $this->actingAs($accountant, 'staff')->post("/admin/payments/{$transaction->id}/reject");

        $response->assertRedirect("/admin/payments/{$transaction->id}");
        $this->assertDatabaseHas('payments_transactions', ['id' => $transaction->id, 'status' => 'exception']);
    }
}
