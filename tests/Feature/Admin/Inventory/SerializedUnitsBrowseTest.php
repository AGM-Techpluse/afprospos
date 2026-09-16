<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerializedUnitsBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_with_inventory_view_can_browse_and_search_serialized_units(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->create(['is_serialized' => true, 'sku_code' => 'CAM-001']);
        InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $shopA->id, 'imei' => '111111111111111', 'status' => 'available']);
        InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $shopB->id, 'imei' => '222222222222222', 'status' => 'sold']);

        $response = $this->actingAs($owner, 'staff')->get('/admin/inventory/serialized-units');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Inventory/SerializedUnits/Index')->has('units.data', 2));

        $imeiSearch = $this->actingAs($owner, 'staff')->get('/admin/inventory/serialized-units?search=111111111111111');
        $imeiSearch->assertInertia(fn ($page) => $page->has('units.data', 1)->where('units.data.0.imei', '111111111111111'));

        $shopFiltered = $this->actingAs($owner, 'staff')->get("/admin/inventory/serialized-units?shop_id={$shopB->id}");
        $shopFiltered->assertInertia(fn ($page) => $page->has('units.data', 1)->where('units.data.0.imei', '222222222222222'));

        $statusFiltered = $this->actingAs($owner, 'staff')->get('/admin/inventory/serialized-units?status=sold');
        $statusFiltered->assertInertia(fn ($page) => $page->has('units.data', 1)->where('units.data.0.status', 'sold'));
    }

    public function test_a_staff_member_without_inventory_view_is_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $marketingStaff = StaffRecord::factory()->create();
        $marketingStaff->assignRole('Marketing Staff'); // no inventory grant at all per config/afprospos.php

        $response = $this->actingAs($marketingStaff, 'staff')->get('/admin/inventory/serialized-units');

        $response->assertForbidden();
    }
}
