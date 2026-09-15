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

class RepairsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_technician_can_create_a_repair_job_and_view_it(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();

        $response = $this->actingAs($technician, 'staff')->post('/admin/repairs', [
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

        $showResponse = $this->actingAs($technician, 'staff')->get("/admin/repairs/{$jobId}");
        $showResponse->assertOk();
        $showResponse->assertInertia(fn ($page) => $page->component('Admin/Repairs/Show')->where('repair.id', $jobId));
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

    public function test_a_cashier_without_repairs_permission_cannot_create_a_repair_job(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // no repairs.* per config/afprospos.php

        $response = $this->actingAs($cashier, 'staff')->get('/admin/repairs/create');

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
