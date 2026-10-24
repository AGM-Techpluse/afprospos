<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The repair cost is the customer-agreed price, only knowable after a `repairable` diagnosis conclusion — distinct from the (now optional) intake labour charge. */
class RepairCostWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_repair_can_be_created_without_a_labour_charge(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($cashier, $shop->id);
        $customer = CustomerRecord::factory()->create();

        $response = $this->actingAs($cashier, 'staff')->post('/admin/repairs', [
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'device_make' => 'Apple',
            'device_model' => 'iPhone 15',
            'device_lock_type' => 'none',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_jobs', [
            'device_model' => 'iPhone 15',
            'labour_charge_minor' => null,
            'repair_cost_minor' => null,
        ]);
    }

    public function test_repair_cost_cannot_be_set_before_diagnosis_is_concluded(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = StaffRecord::factory()->create();
        $manager->assignRole('Manager');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($manager, $shop->id);
        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'diagnosing']);

        $response = $this->actingAs($manager, 'staff')->post("/admin/repairs/{$job->id}/repair-cost", [
            'repair_cost_minor' => 1500000,
        ]);

        $response->assertSessionHas('flash.kind', 'danger');
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_cost_minor' => null]);
    }

    public function test_repair_cost_can_be_set_and_later_updated_after_a_repairable_diagnosis(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = StaffRecord::factory()->create();
        $manager->assignRole('Manager');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($manager, $shop->id);
        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'awaiting_authorization']);

        $setResponse = $this->actingAs($manager, 'staff')->post("/admin/repairs/{$job->id}/repair-cost", [
            'repair_cost_minor' => 1500000,
        ]);
        $setResponse->assertRedirect();
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_cost_minor' => 1500000]);

        $job->update(['repair_status' => 'payment_overdue']);

        $updateResponse = $this->actingAs($manager, 'staff')->post("/admin/repairs/{$job->id}/repair-cost", [
            'repair_cost_minor' => 1800000,
        ]);
        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_cost_minor' => 1800000]);
    }

    public function test_repair_cost_cannot_be_set_once_completed(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = StaffRecord::factory()->create();
        $manager->assignRole('Manager');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($manager, $shop->id);
        $job = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'repair_status' => 'completed',
            'repair_cost_minor' => 1500000,
        ]);

        $response = $this->actingAs($manager, 'staff')->post("/admin/repairs/{$job->id}/repair-cost", [
            'repair_cost_minor' => 2000000,
        ]);

        $response->assertSessionHas('flash.kind', 'danger');
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_cost_minor' => 1500000]);
    }
}
