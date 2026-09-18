<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Warranty;

use Database\Seeders\RolePermissionSeeder;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\ReturnRequestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanProcessReturnRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_technician_can_assess_and_approve_and_an_accountant_can_process_the_refund(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $sale = SaleRecord::factory()->create();
        $returnRequest = ReturnRequestRecord::factory()->create([
            'sale_id' => $sale->id,
            'return_window_expires_at' => now()->addDays(5),
        ]);

        $this->actingAs($technician, 'staff')->post("/admin/warranty/returns/{$returnRequest->id}/assess")->assertRedirect();
        $this->assertDatabaseHas('return_requests', ['id' => $returnRequest->id, 'resolution_state' => 'under_assessment']);

        $this->actingAs($technician, 'staff')->post("/admin/warranty/returns/{$returnRequest->id}/approve")->assertRedirect();
        $this->assertDatabaseHas('return_requests', ['id' => $returnRequest->id, 'resolution_state' => 'approved']);

        $response = $this->actingAs($accountant, 'staff')->post(
            "/admin/warranty/returns/{$returnRequest->id}/process-refund",
            ['payment_transaction_id' => $sale->payment_transaction_id],
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('return_requests', ['id' => $returnRequest->id, 'resolution_state' => 'resolved']);
        $this->assertDatabaseHas('payments_transactions', ['id' => $sale->payment_transaction_id, 'status' => 'refunded']);
    }

    public function test_approving_an_expired_return_requires_the_override_action(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');

        $returnRequest = ReturnRequestRecord::factory()->create(['return_window_expires_at' => now()->subDay()]);

        $this->actingAs($technician, 'staff')->post("/admin/warranty/returns/{$returnRequest->id}/assess");

        $response = $this->actingAs($technician, 'staff')->post("/admin/warranty/returns/{$returnRequest->id}/approve");

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('return_requests', ['id' => $returnRequest->id, 'resolution_state' => 'under_assessment']);

        $override = $this->actingAs($technician, 'staff')->post("/admin/warranty/returns/{$returnRequest->id}/override-approve");
        $override->assertRedirect();
        $this->assertDatabaseHas('return_requests', ['id' => $returnRequest->id, 'resolution_state' => 'approved']);
    }
}
