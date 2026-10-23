<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Repair\Application\Commands\CreateDeviceProblemTagCommand;
use Domain\Repair\Application\Commands\CreateDeviceTypeCommand;
use Domain\Repair\Application\Commands\ReserveRepairPartsCommand;
use Domain\Repair\Application\Handlers\CreateDeviceProblemTagHandler;
use Domain\Repair\Application\Handlers\CreateDeviceTypeHandler;
use Domain\Repair\Application\Handlers\ReserveRepairPartsHandler;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontdesk_staff_can_create_a_repair_job_and_view_it(): void
    {
        $this->seed(RolePermissionSeeder::class);

        // Frontdesk/sales staff do intake now, not technicians — the technician
        // reports test/diagnosis findings back to be recorded, per the corrected workflow.
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($cashier, $shop->id);
        $customer = CustomerRecord::factory()->create();

        $response = $this->actingAs($cashier, 'staff')->post('/admin/repairs', [
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'device_make' => 'Apple',
            'device_model' => 'iPhone 13',
            'labour_charge_minor' => 500000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_jobs', [
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'device_make' => 'Apple',
            'repair_status' => 'received',
        ]);

        $jobId = RepairJobRecord::query()->latest('id')->first()->id;

        $showResponse = $this->actingAs($cashier, 'staff')->get("/admin/repairs/{$jobId}");
        $showResponse->assertOk();
        $showResponse->assertInertia(fn ($page) => $page->component('Admin/Repairs/Show')->where('repair.id', $jobId));
    }

    public function test_repair_detail_shows_the_part_reservations_price_as_snapshotted_not_the_skus_current_price(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($cashier, $shop->id);

        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 1_500_00]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);
        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'diagnosing']);

        app(ReserveRepairPartsHandler::class)->handle(new ReserveRepairPartsCommand(
            repairJobId: $job->id,
            skuId: $sku->id,
            quantity: 1,
            reservedByStaffId: $cashier->id,
        ));

        $sku->update(['selling_price_minor' => 9_999_00]);

        $this->actingAs($cashier, 'staff')
            ->get("/admin/repairs/{$job->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Repairs/Show')
                ->where('repair.part_reservations.0.unit_price_minor', 1_500_00));
    }

    public function test_intake_captures_imei_and_snapshots_selected_problem_tags(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($cashier, $shop->id);
        $customer = CustomerRecord::factory()->create();

        $typeId = app(CreateDeviceTypeHandler::class)->handle(new CreateDeviceTypeCommand('Smartphones', 'phone', 0, $cashier->id));
        $tagId = app(CreateDeviceProblemTagHandler::class)->handle(new CreateDeviceProblemTagCommand(deviceTypeId: $typeId, deviceModelId: null, label: 'Screen', sortOrder: 0, createdByStaffId: $cashier->id));

        $response = $this->actingAs($cashier, 'staff')->post('/admin/repairs', [
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'device_make' => 'Apple',
            'device_model' => 'iPhone 13',
            'device_imei_serial' => '356938035601001',
            'problem_tag_ids' => [$tagId],
            'labour_charge_minor' => 500000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_jobs', ['device_make' => 'Apple', 'device_imei_serial' => '356938035601001']);

        $jobId = RepairJobRecord::query()->latest('id')->first()->id;
        $this->assertDatabaseHas('repair_job_problem_tags', [
            'repair_job_id' => $jobId,
            'device_problem_tag_id' => $tagId,
            'label_snapshot' => 'Screen',
        ]);

        $showResponse = $this->actingAs($cashier, 'staff')->get("/admin/repairs/{$jobId}");
        $showResponse->assertInertia(fn ($page) => $page
            ->where('repair.device_imei_serial', '356938035601001')
            ->where('repair.problem_tags.0.label', 'Screen'));
    }

    public function test_index_renders_for_a_technician(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');

        $response = $this->actingAs($technician, 'staff')->get('/admin/repairs');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Repairs/Index'));
    }

    public function test_a_technician_can_no_longer_create_a_repair_job(): void
    {
        $this->seed(RolePermissionSeeder::class);

        // Frontdesk/sales staff do intake now — the corrected workflow has
        // technicians reporting findings back rather than creating jobs themselves.
        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');

        $response = $this->actingAs($technician, 'staff')->get('/admin/repairs/create');

        $response->assertForbidden();
    }

    public function test_a_marketing_staff_member_without_repairs_permission_cannot_create_a_repair_job(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $marketingStaff = StaffRecord::factory()->create();
        $marketingStaff->assignRole('Marketing Staff'); // no repairs.* per config/afprospos.php

        $response = $this->actingAs($marketingStaff, 'staff')->get('/admin/repairs/create');

        $response->assertForbidden();
    }

    public function test_a_product_staff_member_can_view_but_not_create_a_repair_job(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $productStaff = StaffRecord::factory()->create();
        $productStaff->assignRole('Product Staff'); // repairs.view only

        $indexResponse = $this->actingAs($productStaff, 'staff')->get('/admin/repairs');
        $indexResponse->assertOk();

        $createResponse = $this->actingAs($productStaff, 'staff')->get('/admin/repairs/create');
        $createResponse->assertForbidden();
    }
}
