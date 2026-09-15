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

        $transaction = PaymentTransactionRecord::factory()->create(['status' => 'confirmed']);

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
        ]);

        $resolveResponse->assertRedirect("/admin/payments/{$transaction->id}");
        $this->assertDatabaseHas('payments_transactions', [
            'id' => $transaction->id,
            'status' => 'refunded',
            'dispute_resolved_by_staff_id' => $accountant->id,
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
