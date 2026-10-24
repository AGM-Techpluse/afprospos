<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairSupplementalQuoteRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A technician/frontdesk flags extra cost discovered mid-repair; deciding it needs the same trust tier that set the original quote (`repairs.authorize`), not `repairs.approve-payment` — this is a price decision, not a payment-collection one. */
class SupplementalQuoteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_requests_and_a_manager_decides_a_supplemental_quote(): void
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
            'repair_status' => 'in_progress',
            'repair_cost_minor' => 900000,
        ]);

        $requestResponse = $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$job->id}/supplemental-quote", [
            'amount_minor' => 50000,
            'reason' => 'Extra parts needed beyond the original quote',
        ]);
        $requestResponse->assertRedirect();
        $this->assertDatabaseHas('repair_supplemental_quotes', ['repair_job_id' => $job->id, 'status' => 'pending']);

        $quote = RepairSupplementalQuoteRecord::query()->where('repair_job_id', $job->id)->firstOrFail();

        $decideResponse = $this->actingAs($manager, 'staff')->post("/admin/repairs/{$job->id}/supplemental-quote/{$quote->id}/decide", [
            'approved' => '1',
        ]);
        $decideResponse->assertRedirect();
        $this->assertDatabaseHas('repair_supplemental_quotes', ['id' => $quote->id, 'status' => 'approved']);
    }

    public function test_a_cashier_lacks_permission_to_decide_the_supplemental_quote_they_requested(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $shop->id);

        $job = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'repair_status' => 'in_progress',
            'repair_cost_minor' => 900000,
        ]);

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$job->id}/supplemental-quote", [
            'amount_minor' => 50000,
            'reason' => 'Extra parts needed',
        ]);
        $quote = RepairSupplementalQuoteRecord::query()->where('repair_job_id', $job->id)->firstOrFail();

        $response = $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$job->id}/supplemental-quote/{$quote->id}/decide", [
            'approved' => '1',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('repair_supplemental_quotes', ['id' => $quote->id, 'status' => 'pending']);
    }

    public function test_a_technician_cannot_request_a_supplemental_quote(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $this->grantShopAccess($technician, $shop->id);

        $job = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'repair_status' => 'in_progress',
            'repair_cost_minor' => 900000,
        ]);

        $response = $this->actingAs($technician, 'staff')->post("/admin/repairs/{$job->id}/supplemental-quote", [
            'amount_minor' => 50000,
            'reason' => 'Extra parts needed',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('repair_supplemental_quotes', 0);
    }

    public function test_a_staff_member_without_access_to_the_repairs_shop_is_forbidden_and_causes_no_state_change(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        // Deliberately no grantShopAccess() — this staff member has no access to $shop.

        $job = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'repair_status' => 'in_progress',
            'repair_cost_minor' => 900000,
        ]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$job->id}/supplemental-quote", [
            'amount_minor' => 50000,
            'reason' => 'Extra parts needed',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('repair_supplemental_quotes', 0);
    }
}
