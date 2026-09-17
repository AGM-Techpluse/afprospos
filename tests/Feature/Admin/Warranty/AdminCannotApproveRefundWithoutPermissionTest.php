<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Warranty;

use Database\Seeders\RolePermissionSeeder;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyClaimRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** WAR-BR-06's separation of duties: Technician has warranty.resolve (repair/replace execution) but not warranty.approve-refund. */
class AdminCannotApproveRefundWithoutPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_technician_cannot_approve_a_refund(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');

        $transaction = PaymentTransactionRecord::factory()->create(['status' => 'confirmed']);
        $claim = WarrantyClaimRecord::factory()->create([
            'resolution_state' => 'remedy_selected',
            'selected_remedy' => 'refund',
        ]);

        $response = $this->actingAs($technician, 'staff')
            ->post("/admin/warranty/claims/{$claim->id}/approve-refund", ['payment_transaction_id' => $transaction->id]);

        $response->assertForbidden();
        $this->assertDatabaseHas('warranty_claims', ['id' => $claim->id, 'resolution_state' => 'remedy_selected']);
        $this->assertDatabaseHas('payments_transactions', ['id' => $transaction->id, 'status' => 'confirmed']);
    }
}
