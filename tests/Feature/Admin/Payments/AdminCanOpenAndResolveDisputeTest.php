<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Payments;

use Database\Seeders\RolePermissionSeeder;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanOpenAndResolveDisputeTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_can_open_and_then_resolve_a_dispute(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $transaction = PaymentTransactionRecord::factory()->create(['status' => 'confirmed', 'amount_minor' => 5000]);

        $openResponse = $this->actingAs($accountant, 'staff')->post("/admin/payments/{$transaction->id}/dispute", [
            'dispute_proof_reference' => 'chargeback-notice.pdf',
        ]);

        $openResponse->assertRedirect("/admin/payments/{$transaction->id}");
        $this->assertDatabaseHas('payments_transactions', [
            'id' => $transaction->id,
            'status' => 'disputed',
            'dispute_proof_reference' => 'chargeback-notice.pdf',
        ]);

        $resolveResponse = $this->actingAs($accountant, 'staff')->post("/admin/payments/{$transaction->id}/dispute/resolve", [
            'resolution' => 'refunded',
            'amount_minor' => 5000,
        ]);

        $resolveResponse->assertRedirect("/admin/payments/{$transaction->id}");
        $this->assertDatabaseHas('payments_transactions', [
            'id' => $transaction->id,
            'status' => 'refunded',
            'dispute_resolved_by_staff_id' => $accountant->id,
        ]);
        $this->assertDatabaseHas('payment_refunds', [
            'payment_transaction_id' => $transaction->id,
            'amount_minor' => 5000,
            'refunded_by_staff_id' => $accountant->id,
            'source' => 'dispute_resolution',
        ]);
    }

    public function test_resolving_a_dispute_as_refunded_with_a_partial_amount_leaves_the_payment_partially_refunded(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $transaction = PaymentTransactionRecord::factory()->create(['status' => 'disputed', 'amount_minor' => 5000]);

        $resolveResponse = $this->actingAs($accountant, 'staff')->post("/admin/payments/{$transaction->id}/dispute/resolve", [
            'resolution' => 'refunded',
            'amount_minor' => 2000,
        ]);

        $resolveResponse->assertRedirect("/admin/payments/{$transaction->id}");
        $this->assertDatabaseHas('payments_transactions', [
            'id' => $transaction->id,
            'status' => 'partially_refunded',
        ]);
        $this->assertDatabaseHas('payment_refunds', [
            'payment_transaction_id' => $transaction->id,
            'amount_minor' => 2000,
            'source' => 'dispute_resolution',
        ]);
    }

    public function test_a_staff_member_without_payments_dispute_cannot_open_a_dispute(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $transaction = PaymentTransactionRecord::factory()->create(['status' => 'confirmed']);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/payments/{$transaction->id}/dispute");

        $response->assertForbidden();
        $this->assertDatabaseHas('payments_transactions', ['id' => $transaction->id, 'status' => 'confirmed']);
    }
}
