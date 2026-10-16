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

    public function test_an_admin_can_manage_device_models_through_the_ddd_backed_endpoints(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        ShopRecord::factory()->create();

        $typeId = DB::table('device_types')->insertGetId(['label' => 'Smartphones', 'icon' => 'phone', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $brandId = DB::table('device_brands')->insertGetId(['device_type_id' => $typeId, 'name' => 'Apple', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $otherBrandId = DB::table('device_brands')->insertGetId(['device_type_id' => $typeId, 'name' => 'Samsung', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $storeResponse = $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/models', [
            'device_brand_id' => $brandId,
            'name' => 'iPhone 15 Pro',
        ]);
        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('device_models', ['device_brand_id' => $brandId, 'name' => 'iPhone 15 Pro']);
        $modelId = DB::table('device_models')->where('name', 'iPhone 15 Pro')->value('id');

        // Idempotent quick-add: posting the same (brand, name) again must not create a duplicate row.
        $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/models', [
            'device_brand_id' => $brandId,
            'name' => 'iPhone 15 Pro',
        ])->assertRedirect();
        $this->assertDatabaseCount('device_models', 1);

        $updateResponse = $this->actingAs($owner, 'staff')->put("/admin/settings/device-catalog/models/{$modelId}", [
            'device_brand_id' => $otherBrandId,
            'name' => 'iPhone 15 Pro Max',
        ]);
        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('device_models', ['id' => $modelId, 'device_brand_id' => $otherBrandId, 'name' => 'iPhone 15 Pro Max']);

        $destroyResponse = $this->actingAs($owner, 'staff')->delete("/admin/settings/device-catalog/models/{$modelId}");
        $destroyResponse->assertRedirect();
        $this->assertDatabaseMissing('device_models', ['id' => $modelId]);

        $this->assertDatabaseHas('audit_logs', ['module' => 'Repair', 'event_type' => 'DeviceModelCreated']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Repair', 'event_type' => 'DeviceModelUpdated']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Repair', 'event_type' => 'DeviceModelDeleted']);
    }

    public function test_a_model_specific_fault_overrides_its_type_default_but_a_bare_model_falls_back_to_it(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        ShopRecord::factory()->create();

        $typeId = DB::table('device_types')->insertGetId(['label' => 'Smartphones', 'icon' => 'phone', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $brandId = DB::table('device_brands')->insertGetId(['device_type_id' => $typeId, 'name' => 'Apple', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $overriddenModelId = DB::table('device_models')->insertGetId(['device_brand_id' => $brandId, 'name' => 'iPhone 15 Pro', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $bareModelId = DB::table('device_models')->insertGetId(['device_brand_id' => $brandId, 'name' => 'iPhone SE', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/problem-tags', [
            'device_type_id' => $typeId,
            'label' => 'Screen (default)',
        ])->assertRedirect();

        $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/problem-tags', [
            'device_model_id' => $overriddenModelId,
            'label' => 'Screen (Pro-specific)',
            'commission_amount_minor' => 300000,
        ])->assertRedirect();

        $this->assertDatabaseHas('device_problem_tags', [
            'device_model_id' => $overriddenModelId,
            'device_type_id' => $typeId,
            'label' => 'Screen (Pro-specific)',
        ]);

        $response = $this->actingAs($owner, 'staff')->get('/admin/settings/device-catalog');
        $response->assertOk();

        $models = collect($response->viewData('page')['props']['deviceTypes'][0]['brands'][0]['models'])->keyBy('id');
        $overridden = $models[$overriddenModelId];
        $bare = $models[$bareModelId];

        $this->assertTrue($overridden['has_model_specific_faults']);
        $this->assertSame(['Screen (Pro-specific)'], collect($overridden['fixable_faults'])->pluck('label')->all());

        $this->assertFalse($bare['has_model_specific_faults']);
        $this->assertSame(['Screen (default)'], collect($bare['fixable_faults'])->pluck('label')->all());
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
