<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairDownPaymentApprovalRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Frontdesk records the down payment it collected; a Manager/Shop Owner has to approve it before the repair is authorized and the real payment transaction exists. */
class RepairDownPaymentApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_requests_and_a_manager_approves_the_down_payment(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $shop->id);
        $manager = StaffRecord::factory()->create();
        $manager->assignRole('Manager');
        $this->grantShopAccess($manager, $shop->id);

        $job = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'repair_status' => 'awaiting_authorization',
            'down_payment_required_minor' => 200000,
        ]);

        $requestResponse = $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$job->id}/down-payment-approval", [
            'amount_minor' => 200000,
            'method' => 'cash',
        ]);
        $requestResponse->assertRedirect();
        $this->assertDatabaseHas('repair_down_payment_approvals', ['repair_job_id' => $job->id, 'status' => 'pending']);
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'awaiting_authorization']);

        $approval = RepairDownPaymentApprovalRecord::query()
            ->where('repair_job_id', $job->id)->firstOrFail();

        $decideResponse = $this->actingAs($manager, 'staff')->post("/admin/repairs/{$job->id}/down-payment-approval/{$approval->id}/decide", [
            'approved' => '1',
        ]);
        $decideResponse->assertRedirect();
        $this->assertDatabaseHas('repair_down_payment_approvals', ['id' => $approval->id, 'status' => 'approved']);
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'in_progress']);
        $this->assertDatabaseHas('payments_transactions', [
            'payable_type' => 'repair_job',
            'payable_id' => $job->id,
            'purpose' => 'repair_down_payment',
            'amount_minor' => 200000,
            'status' => 'confirmed',
        ]);
    }

    public function test_a_cashier_lacks_permission_to_decide_the_down_payment_approval_they_requested(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $shop->id);

        $job = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'repair_status' => 'awaiting_authorization',
            'down_payment_required_minor' => 200000,
        ]);

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$job->id}/down-payment-approval", [
            'amount_minor' => 200000,
            'method' => 'cash',
        ]);
        $approval = RepairDownPaymentApprovalRecord::query()
            ->where('repair_job_id', $job->id)->firstOrFail();

        $response = $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$job->id}/down-payment-approval/{$approval->id}/decide", [
            'approved' => '1',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('repair_down_payment_approvals', ['id' => $approval->id, 'status' => 'pending']);
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'awaiting_authorization']);
    }

    public function test_a_technician_cannot_set_a_down_payment_requirement_or_record_receipt(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $this->grantShopAccess($technician, $shop->id);

        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'awaiting_authorization']);

        $authorizeResponse = $this->actingAs($technician, 'staff')->post("/admin/repairs/{$job->id}/authorize", [
            'down_payment_required_minor' => 200000,
        ]);
        $authorizeResponse->assertForbidden();

        $requestResponse = $this->actingAs($technician, 'staff')->post("/admin/repairs/{$job->id}/down-payment-approval", [
            'amount_minor' => 200000,
            'method' => 'cash',
        ]);
        $requestResponse->assertForbidden();

        $this->assertDatabaseCount('payments_transactions', 0);
    }
}
