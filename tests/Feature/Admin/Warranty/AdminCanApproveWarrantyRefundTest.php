<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Warranty;

use Database\Seeders\RolePermissionSeeder;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyClaimRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanApproveWarrantyRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_accountant_can_approve_a_refund_for_a_remedy_selected_claim(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $transaction = PaymentTransactionRecord::factory()->create(['status' => 'confirmed']);
        $claim = WarrantyClaimRecord::factory()->create([
            'resolution_state' => 'remedy_selected',
            'selected_remedy' => 'refund',
        ]);

        $response = $this->actingAs($accountant, 'staff')
            ->post("/admin/warranty/claims/{$claim->id}/approve-refund", ['payment_transaction_id' => $transaction->id]);

        $response->assertRedirect();
        $this->assertDatabaseHas('warranty_claims', ['id' => $claim->id, 'resolution_state' => 'resolved']);
        $this->assertDatabaseHas('payments_transactions', ['id' => $transaction->id, 'status' => 'refunded']);
    }
}
