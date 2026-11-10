<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BulkAddDeviceProblemTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_with_repairs_manage_can_bulk_add_existing_fault_labels_to_a_model(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        ShopRecord::factory()->create();

        $typeId = DB::table('device_types')->insertGetId(['label' => 'Smartphones', 'icon' => 'phone', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $brandId = DB::table('device_brands')->insertGetId(['device_type_id' => $typeId, 'name' => 'Apple', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $sourceModelId = DB::table('device_models')->insertGetId(['device_brand_id' => $brandId, 'name' => 'iPhone 15 Pro', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $targetModelId = DB::table('device_models')->insertGetId(['device_brand_id' => $brandId, 'name' => 'iPhone 14', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()]);

        // Faults already typed elsewhere under the same device type: one type-level default, one on a sibling model.
        $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/problem-tags', [
            'device_type_id' => $typeId,
            'label' => 'Screen',
        ])->assertRedirect();

        $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/problem-tags', [
            'device_model_id' => $sourceModelId,
            'label' => 'Face ID sensor',
        ])->assertRedirect();

        $suggestionsResponse = $this->actingAs($owner, 'staff')->get("/admin/settings/device-catalog/problem-tags/suggestions?device_type_id={$typeId}");
        $suggestionsResponse->assertOk();
        $suggestionsResponse->assertJson(['labels' => ['Face ID sensor', 'Screen']]);

        $bulkResponse = $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/problem-tags/bulk', [
            'device_model_id' => $targetModelId,
            'labels' => ['Screen', 'Face ID sensor'],
            'starting_sort_order' => 0,
        ]);
        $bulkResponse->assertRedirect();

        $this->assertDatabaseHas('device_problem_tags', [
            'device_type_id' => $typeId,
            'device_model_id' => $targetModelId,
            'label' => 'Screen',
        ]);
        $this->assertDatabaseHas('device_problem_tags', [
            'device_type_id' => $typeId,
            'device_model_id' => $targetModelId,
            'label' => 'Face ID sensor',
        ]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Repair', 'event_type' => 'DeviceProblemTagsBulkAdded']);

        // Resubmitting the same labels for the same model is a no-op, not a duplicate.
        $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/problem-tags/bulk', [
            'device_model_id' => $targetModelId,
            'labels' => ['Screen', 'Face ID sensor'],
            'starting_sort_order' => 2,
        ])->assertRedirect();

        $this->assertSame(2, DB::table('device_problem_tags')->where('device_model_id', $targetModelId)->count());
    }

    public function test_a_staff_member_without_repairs_manage_cannot_bulk_add_problem_tags(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // no repairs.manage per config/afprospos.php
        ShopRecord::factory()->create();

        $typeId = DB::table('device_types')->insertGetId(['label' => 'Smartphones', 'icon' => 'phone', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $brandId = DB::table('device_brands')->insertGetId(['device_type_id' => $typeId, 'name' => 'Apple', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $modelId = DB::table('device_models')->insertGetId(['device_brand_id' => $brandId, 'name' => 'iPhone 14', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->actingAs($cashier, 'staff')->post('/admin/settings/device-catalog/problem-tags/bulk', [
            'device_model_id' => $modelId,
            'labels' => ['Screen'],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('device_problem_tags', ['device_model_id' => $modelId]);
    }
}
