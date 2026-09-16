<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeviceCatalogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_with_repairs_manage_can_build_out_the_device_catalog(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();

        $typeResponse = $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/types', [
            'label' => 'Smartphones',
            'icon' => 'phone',
        ]);
        $typeResponse->assertRedirect();
        $this->assertDatabaseHas('device_types', ['label' => 'Smartphones']);
        $typeId = DB::table('device_types')->where('label', 'Smartphones')->value('id');

        $brandResponse = $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/brands', [
            'device_type_id' => $typeId,
            'name' => 'Apple',
        ]);
        $brandResponse->assertRedirect();
        $this->assertDatabaseHas('device_brands', ['device_type_id' => $typeId, 'name' => 'Apple']);

        $tagResponse = $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/problem-tags', [
            'device_type_id' => $typeId,
            'label' => 'Screen',
        ]);
        $tagResponse->assertRedirect();
        $this->assertDatabaseHas('device_problem_tags', ['device_type_id' => $typeId, 'label' => 'Screen']);
        $tagId = DB::table('device_problem_tags')->where('label', 'Screen')->value('id');

        $attachResponse = $this->actingAs($owner, 'staff')->post("/admin/settings/device-catalog/problem-tags/{$tagId}/suggested-parts", [
            'sku_id' => $sku->id,
        ]);
        $attachResponse->assertRedirect();
        $this->assertDatabaseHas('device_problem_suggested_skus', ['device_problem_tag_id' => $tagId, 'sku_id' => $sku->id]);

        $indexResponse = $this->actingAs($owner, 'staff')->get('/admin/settings/device-catalog');
        $indexResponse->assertOk();
        $indexResponse->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/DeviceCatalog/Index')
            ->where('deviceTypes.0.label', 'Smartphones')
            ->where('deviceTypes.0.brands.0.name', 'Apple')
            ->where('deviceTypes.0.problem_tags.0.label', 'Screen')
            ->where('deviceTypes.0.problem_tags.0.suggested_parts.0.sku_id', $sku->id));

        $this->assertDatabaseHas('audit_logs', ['module' => 'Repair', 'event_type' => 'DeviceTypeCreated']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Repair', 'event_type' => 'DeviceBrandCreated']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Repair', 'event_type' => 'DeviceProblemTagCreated']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Repair', 'event_type' => 'DeviceProblemSuggestedPartAttached']);
    }

    public function test_a_staff_member_without_repairs_manage_cannot_edit_the_device_catalog(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // no repairs.manage per config/afprospos.php

        $response = $this->actingAs($cashier, 'staff')->post('/admin/settings/device-catalog/types', [
            'label' => 'Smartphones',
            'icon' => 'phone',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('device_types', ['label' => 'Smartphones']);
    }
}
