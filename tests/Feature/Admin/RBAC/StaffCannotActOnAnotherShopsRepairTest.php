<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RBAC;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** BRD RBAC-05: holding the `repairs.*` permission proves a staff member can do that action in general — it never proves this specific repair's shop was granted to them. */
class StaffCannotActOnAnotherShopsRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_granted_only_shop_a_cannot_view_a_repair_belonging_to_shop_b(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $shopA->id);

        $job = RepairJobRecord::factory()->create(['shop_id' => $shopB->id]);

        $response = $this->actingAs($cashier, 'staff')->get("/admin/repairs/{$job->id}");

        $response->assertForbidden();
    }

    public function test_a_manager_granted_only_shop_a_cannot_complete_a_repair_belonging_to_shop_b(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();

        $manager = StaffRecord::factory()->create();
        $manager->assignRole('Manager');
        $this->grantShopAccess($manager, $shopA->id);

        $job = RepairJobRecord::factory()->create(['shop_id' => $shopB->id, 'repair_status' => 'in_progress']);

        $response = $this->actingAs($manager, 'staff')->post("/admin/repairs/{$job->id}/complete", [
            'resolution_notes' => 'Attempted cross-shop access',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'in_progress']);
    }

    public function test_a_cashier_cannot_create_a_repair_for_a_shop_they_are_not_granted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $shopA->id);

        $createResponse = $this->actingAs($cashier, 'staff')->get('/admin/repairs/create');
        $createResponse->assertInertia(fn ($page) => $page
            ->where('shops.0.id', $shopA->id)
            ->has('shops', 1));

        $customer = CustomerRecord::factory()->create();

        $storeResponse = $this->actingAs($cashier, 'staff')->post('/admin/repairs', [
            'shop_id' => $shopB->id,
            'customer_id' => $customer->id,
            'device_make' => 'Apple',
            'device_model' => 'iPhone 15',
            'device_lock_type' => 'none',
            'labour_charge_minor' => 500000,
        ]);

        $storeResponse->assertForbidden();
        $this->assertDatabaseMissing('repair_jobs', ['shop_id' => $shopB->id, 'device_model' => 'iPhone 15']);
    }

    public function test_the_shop_owner_can_act_on_any_shops_repair(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($owner, 'staff')->get("/admin/repairs/{$job->id}");

        $response->assertOk();
    }
}
